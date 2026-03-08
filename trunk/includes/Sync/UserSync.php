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

	public function __construct() {
		add_action( 'user_register', [ $this, 'on_register' ], 10, 2 );
	}

	/**
	 * Fires after a new user is registered.
	 *
	 * @param int   $user_id User ID.
	 * @param array $userdata User data array.
	 */
	public function on_register( int $user_id, array $userdata = [] ): void {
		$settings   = get_option( 'ctaforge_settings', [] );
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
		$client->subscribe(
			$user->user_email,
			$list_id,
			[
				'firstName' => $user->first_name ?: '',
				'lastName'  => $user->last_name ?: '',
				'tags'      => [ 'wordpress-user' ],
			]
		);
		// We deliberately ignore errors here — user registration must not fail
		// if CTAForge is unreachable.
	}
}
