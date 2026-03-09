<?php
/**
 * Syncs newly registered WordPress users to CTAForge.
 *
 * Fires only when "Sync WordPress Users" is enabled in settings.
 *
 * @package CTAForge\Sync
 */

namespace CTAForge\Sync;

/**
 * Hooks into user registration and profile update to sync contacts.
 */
class UserSync {

	/**
	 * Constructor — registers WordPress hooks.
	 */
	public function __construct() {
		add_action( 'user_register', array( $this, 'on_register' ), 10, 2 );
	}

	/**
	 * Fires after a new user is registered.
	 *
	 * @param int   $user_id User ID.
	 * @param array $_userdata User data array.
	 */
	public function on_register( int $user_id, array $_userdata = array() ): void {
		$settings   = get_option( 'ctaforge_settings', array() );
		$sync_users = $settings['sync_users'] ?? '0';
		$list_id    = $settings['default_list'] ?? '';

		if ( '1' !== $sync_users || empty( $list_id ) ) {
			return;
		}

		$user = get_userdata( $user_id );
		if ( ! $user || ! $user->user_email ) {
			return;
		}

		$client = \CTAForge\Api\Client::make();

		$subscribe_result = $client->subscribe(
			$user->user_email,
			$list_id,
			array(
				'firstName' => ( '' !== $user->first_name ? $user->first_name : '' ),
				'lastName'  => ( '' !== $user->last_name ? $user->last_name : '' ),
				'tags'      => array( 'wordpress-user' ),
			)
		);

		// Only track the event if subscribe succeeded.
		if ( ! is_wp_error( $subscribe_result ) ) {
			$client->track_event(
				$user->user_email,
				'user_registered',
				array(
					'wp_user_id'   => $user_id,
					'display_name' => $user->display_name,
					'roles'        => (array) $user->roles,
					'site_url'     => get_site_url(),
				)
			);
		}
		// We deliberately ignore all errors — user registration must not fail
		// if CTAForge is unreachable.
	}
}
