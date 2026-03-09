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

	/**
	 * CTAForge API key (ctaf_…).
	 *
	 * @var string
	 */
	private string $api_key;

	/**
	 * GraphQL endpoint URL.
	 *
	 * @var string
	 */
	private string $endpoint;

	/**
	 * Constructor.
	 *
	 * @param string $api_key  CTAForge API key (ctaf_…).
	 * @param string $endpoint Optional: override the GraphQL endpoint.
	 */
	public function __construct( string $api_key, string $endpoint = '' ) {
		$this->api_key  = $api_key;
		$this->endpoint = '' !== $endpoint ? $endpoint : CTAFORGE_API_DEFAULT;
	}

	/**
	 * Executes a GraphQL query or mutation.
	 *
	 * @param  string $query     GraphQL document.
	 * @param  array  $variables Variables map.
	 * @return array|WP_Error    Decoded `data` key on success, WP_Error on failure.
	 */
	public function query( string $query, array $variables = array() ): array|WP_Error {
		$body = wp_json_encode(
			array(
				'query'     => $query,
				'variables' => $variables,
			)
		);

		if ( false === $body ) {
			return new WP_Error( 'ctaforge_encode', __( 'Failed to encode request body.', 'ctaforge' ) );
		}

		$response = wp_remote_post(
			$this->endpoint,
			array(
				'headers' => array(
					'Content-Type'  => 'application/json',
					'Authorization' => 'Bearer ' . $this->api_key,
					'X-Source'      => 'ctaforge-wordpress/' . CTAFORGE_VERSION,
				),
				'body'    => $body,
				'timeout' => 15,
			)
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

		return $data['data'] ?? array();
	}

	// ─── Audience helpers ────────────────────────────────────────────────────────

	/**
	 * Creates or updates a contact and subscribes them to a list.
	 *
	 * Uses the `upsertContact` GraphQL mutation — existing contacts are merged,
	 * new contacts are created. Safe to call on every form submission.
	 *
	 * @param  string $email    Contact email address.
	 * @param  string $list_id  UUID of the CTAForge list.
	 * @param  array  $fields   Optional: firstName, lastName, tags[], customFields{}.
	 * @return array|WP_Error   { id, email, status, created } on success.
	 */
	public function subscribe( string $email, string $list_id, array $fields = array() ): array|WP_Error {
		$mutation = '
			mutation UpsertContact($input: UpsertContactInput!) {
				upsertContact(input: $input) {
					id
					email
					status
					created
				}
			}
		';

		$variables = array_merge(
			array(
				'email'  => $email,
				'listId' => $list_id,
				'source' => 'wordpress',
			),
			$fields
		);

		$result = $this->query( $mutation, array( 'input' => $variables ) );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return $result['upsertContact'] ?? array();
	}

	/**
	 * Records a behavioral event for a contact in CTAForge.
	 *
	 * This is how the WordPress plugin tells CTAForge "this contact did X":
	 *   - Filled in a form  → event_type: 'form_submitted'
	 *   - Placed an order   → event_type: 'purchase',       props: { order_id, amount, currency }
	 *   - Order completed   → event_type: 'order_completed', props: { order_id }
	 *   - Order refunded    → event_type: 'refund',          props: { order_id, amount }
	 *   - User registered   → event_type: 'user_registered', props: { wp_user_id }
	 *
	 * Events surface in the contact activity timeline and can be used
	 * for segmentation and automation triggers.
	 *
	 * @param  string        $email       Contact email address.
	 * @param  string        $event_type  Event type slug.
	 * @param  array         $properties  Arbitrary event properties (key-value).
	 * @param  string        $source      Source system (defaults to 'WordPress').
	 * @param  DateTime|null $occurred_at Optional; defaults to now.
	 * @return array|WP_Error             { id, contactEmail, eventType, occurredAt } on success.
	 */
	public function track_event(
		string $email,
		string $event_type,
		array $properties = array(),
		string $source = 'WordPress',
		?\DateTime $occurred_at = null
	): array|WP_Error {
		$mutation = '
			mutation TrackContactEvent($input: TrackContactEventInput!) {
				trackContactEvent(input: $input) {
					id
					contactEmail
					eventType
					source
					properties
					occurredAt
				}
			}
		';

		$variables = array(
			'contactEmail' => $email,
			'eventType'    => $event_type,
			'source'       => $source,
			'properties'   => empty( $properties ) ? new \stdClass() : $properties,
		);

		if ( null !== $occurred_at ) {
			$variables['occurredAt'] = $occurred_at->format( \DateTime::RFC3339 );
		}

		$result = $this->query( $mutation, array( 'input' => $variables ) );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return $result['trackContactEvent'] ?? array();
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
			array_column( $result['lists']['edges'] ?? array(), 'node' ),
			null
		);
	}

	/**
	 * Returns a singleton Client configured from plugin settings.
	 */
	public static function make(): self {
		$settings = get_option( 'ctaforge_settings', array() );
		return new self(
			$settings['api_key'] ?? '',
			$settings['api_url'] ?? CTAFORGE_API_DEFAULT
		);
	}
}
