<?php
/**
 * Plugin core — bootstraps all modules.
 *
 * @package CTAForge
 */

namespace CTAForge;

/**
 * Main plugin class (singleton).
 */
final class Plugin {

	/** @var self|null */
	private static ?self $instance = null;

	/** Private constructor — use instance(). */
	private function __construct() {
		$this->load_textdomain();
		$this->init_modules();
	}

	/**
	 * Returns (or creates) the single plugin instance.
	 */
	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Loads the plugin text domain for translations.
	 */
	private function load_textdomain(): void {
		load_plugin_textdomain(
			'ctaforge',
			false,
			dirname( plugin_basename( CTAFORGE_PLUGIN_FILE ) ) . '/languages'
		);
	}

	/**
	 * Registers all feature modules.
	 */
	private function init_modules(): void {
		// Admin settings (back-end only).
		if ( is_admin() ) {
			new Admin\Menu();
			new Admin\Settings();
		}

		// Front-end features.
		new Forms\Shortcode();
		new Forms\Block();

		// User sync on registration / profile update.
		new Sync\UserSync();

		// WooCommerce integration (optional — only if WC active).
		if ( class_exists( 'WooCommerce' ) ) {
			new WooCommerce\OrderSync();
		}
	}
}
