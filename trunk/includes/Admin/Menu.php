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

	/**
	 * Constructor — registers WordPress hooks.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
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
			array( new Settings(), 'render_page' )
		);
	}
}
