<?php
/**
 * Handles plugin activation and deactivation.
 *
 * @package CTAForge
 */

namespace CTAForge;

/**
 * Installer — runs on activate/deactivate hooks.
 */
class Installer {

	/**
	 * Fired on plugin activation.
	 * Sets default options if not already present.
	 */
	public static function activate(): void {
		if ( ! get_option( 'ctaforge_settings' ) ) {
			update_option( 'ctaforge_settings', [
				'api_key'     => '',
				'api_url'     => CTAFORGE_API_DEFAULT,
				'default_list' => '',
			] );
		}

		// Flush rewrite rules so shortcodes/pages are available immediately.
		flush_rewrite_rules();
	}

	/**
	 * Fired on plugin deactivation.
	 */
	public static function deactivate(): void {
		flush_rewrite_rules();
	}
}
