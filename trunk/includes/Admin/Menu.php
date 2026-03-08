<?php
/**
 * Registers the CTAForge menu in the WordPress admin.
 *
 * @package CTAForge\Admin
 */

namespace CTAForge\Admin;

/**
 * Adds the CTAForge settings page under Settings menu.
 */
class Menu {

	public function __construct() {
		add_action( 'admin_menu', [ $this, 'register_menu' ] );
	}

	/**
	 * Registers the admin menu entry.
	 */
	public function register_menu(): void {
		add_options_page(
			__( 'CTAForge Settings', 'ctaforge' ),
			__( 'CTAForge', 'ctaforge' ),
			'manage_options',
			'ctaforge',
			[ new Settings(), 'render_page' ]
		);
	}
}
