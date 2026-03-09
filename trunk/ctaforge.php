<?php
/**
 * Plugin Name: CTAForge
 * Plugin URI:  https://ctaforge.com
 * Description: Connect your WordPress site to CTAForge — embed signup forms, sync contacts automatically and track email engagement in real time.
 * Version:     1.0.0
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * Author:      CTAForge
 * Author URI:  https://ctaforge.com
 * License:     GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: ctaforge
 * Domain Path: /languages
 *
 * @package CTAForge
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'CTAFORGE_VERSION', '1.0.0' );
define( 'CTAFORGE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'CTAFORGE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'CTAFORGE_PLUGIN_FILE', __FILE__ );
define( 'CTAFORGE_API_DEFAULT', 'https://api.ctaforge.com/graphql' );

// ─── Autoloader ────────────────────────────────────────────────────────────────
spl_autoload_register(
	function ( string $classname ): void {
		$prefix = 'CTAForge\\';
		$base   = CTAFORGE_PLUGIN_DIR . 'includes/';

		if ( strncmp( $classname, $prefix, strlen( $prefix ) ) !== 0 ) {
				return;
		}

		$relative = str_replace( '\\', '/', substr( $classname, strlen( $prefix ) ) );
		$file     = $base . $relative . '.php';

		if ( is_readable( $file ) ) {
			require $file;
		}
	}
);

// ─── Bootstrap ─────────────────────────────────────────────────────────────────
/**
 * Returns the single instance of the plugin.
 */
function ctaforge(): \CTAForge\Plugin {
	return \CTAForge\Plugin::instance();
}

ctaforge();

// ─── Activation / Deactivation hooks ─────────────────────────────────────────
register_activation_hook( __FILE__, array( 'CTAForge\\Installer', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'CTAForge\\Installer', 'deactivate' ) );
