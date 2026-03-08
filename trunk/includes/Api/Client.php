<?php
/**
 * Lightweight GraphQL client for the CTAForge API.
 *
 * Uses wp_remote_post() so WordPress HTTP API handles
 * proxy, SSL and timeout settings automatically.
 *
 * @package CTAForge\Api
 */

namespace CTAForge\Api;

use WP_Error;

/**
 * GraphQL client.
 *
 * Usage:
 *   $client   = new Client( $api_key );
 *   $result   = $client->query( '...', [ 'email' => 'a@b.com' ] );
 *   if ( is_wp_error( $result ) ) { ... }
 */
class Client {

	/** @var string */
	private string $api_key;

	/** @var string GraphQL endpoint URL. */
	private string $endpoint;

	/**
	 * @param string $api_key  CTAForge API key (ctaf_…).
	 * @param string $endpoint Optional: override the GraphQL endpoint.
	 */
	public function __construct( string $api_key, string $endpoint = '' ) {
		$this->api_key  = $api_key;
		$this->endpoint = $endpoint ?: CTAFORGE_API_DEFAULT;
	}

	/**
	 * Executes a GraphQL query or mutation.
	 *
	 * @param  string $query     GraphQL document.
	 * @param  array  $variables Variables map.
	 * @return array|WP_Error    Decoded `data` key on success, WP_Error on failure.
	 */
	public function query( string $query, array $variables = [] ): array|WP_Error {
		$body = wp_json_encode( [
			'query'     => $query,
			'variables' => $variables,
		] );

		if ( false === $body ) {
			return new WP_Error( 'ctaforge_encode', __( 'Failed to encode request body.', 'ctaforge' ) );
		}

		$response = wp_remote_post(
			$this->endpoint,
			[
				'headers' => [
					'Content-Type'  => 'application/json',
					'Authorization' => 'Bearer ' . $this->api_key,
					'X-Source'      => 'ctaforge-wordpress/' . CTAFORGE_VERSION,
				],
				'body'    => $body,
				'timeout' => 15,
			]
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$status = wp_remote_retrieve_response_code( $response );
		$raw    = wp_remote_retrieve_body( $response );
		$data   = json_decode( $raw, true );

		if ( ! is_array( $data ) ) {
			return new WP_Error(
				'ctaforge_parse',
				sprintf(
					/* translators: %d: HTTP status code */
					__( 'CTAForge API returned non-JSON response (HTTP %d).', 'ctaforge' ),
					$status
				)
			);
		}

		if ( ! empty( $data['errors'] ) ) {
			$message = $data['errors'][0]['message'] ?? __( 'Unknown API error.', 'ctaforge' );
			return new WP_Error( 'ctaforge_api', $message, $data['errors'] );
		}

		return $data['data'] ?? [];
	}

	// ─── Audience helpers ────────────────────────────────────────────────────────

	/**
	 * Subscribes a contact to a list.
	 *
	 * @param  string $email    Contact email address.
	 * @param  string $list_id  UUID of the CTAForge list.
	 * @param  array  $fields   Extra fields: first_name, last_name, custom_fields…
	 * @return array|WP_Error
	 */
	public function subscribe( string $email, string $list_id, array $fields = [] ): array|WP_Error {
		$mutation = '
			mutation Subscribe($input: UpsertContactInput!) {
				upsertContact(input: $input) {
					id
					email
					status
				}
			}
		';

		return $this->query( $mutation, [
			'input' => array_merge(
				[ 'email' => $email, 'listId' => $list_id ],
				$fields
			),
		] );
	}

	/**
	 * Fetches all available lists for the authenticated tenant.
	 *
	 * @return array|WP_Error
	 */
	public function get_lists(): array|WP_Error {
		$query = '
			query GetLists {
				lists(first: 100) {
					edges {
						node { id name subscriberCount }
					}
				}
			}
		';

		$result = $this->query( $query );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return array_column(
			array_column( $result['lists']['edges'] ?? [], 'node' ),
			null
		);
	}

	/**
	 * Returns a singleton Client configured from plugin settings.
	 */
	public static function make(): self {
		$settings = get_option( 'ctaforge_settings', [] );
		return new self(
			$settings['api_key']  ?? '',
			$settings['api_url']  ?? CTAFORGE_API_DEFAULT
		);
	}
}
