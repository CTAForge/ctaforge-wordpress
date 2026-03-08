<?php
/**
 * CTAForge Signup Form — Gutenberg block.
 *
 * Registers a server-side-rendered block that uses the Shortcode
 * renderer internally. Attributes mirror the shortcode atts.
 *
 * @package CTAForge\Forms
 */

namespace CTAForge\Forms;

/**
 * Registers the `ctaforge/signup-form` Gutenberg block.
 */
class Block {

	public function __construct() {
		add_action( 'init', [ $this, 'register_block' ] );
	}

	/**
	 * Registers the block type with its attributes and render callback.
	 */
	public function register_block(): void {
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}

		register_block_type(
			'ctaforge/signup-form',
			[
				'editor_script'   => 'ctaforge-block-editor',
				'editor_style'    => 'ctaforge-block-editor-style',
				'style'           => 'ctaforge-form',
				'attributes'      => [
					'listId'      => [ 'type' => 'string', 'default' => '' ],
					'title'       => [ 'type' => 'string', 'default' => 'Subscribe to our newsletter' ],
					'description' => [ 'type' => 'string', 'default' => '' ],
					'button'      => [ 'type' => 'string', 'default' => 'Subscribe' ],
					'placeholder' => [ 'type' => 'string', 'default' => 'Your email address' ],
					'fields'      => [ 'type' => 'string', 'default' => '' ],
					'className'   => [ 'type' => 'string', 'default' => '' ],
				],
				'render_callback' => [ $this, 'render' ],
			]
		);

		$this->register_editor_script();
	}

	/**
	 * Server-side render callback — delegates to the Shortcode renderer.
	 *
	 * @param  array  $atts Block attributes.
	 * @return string       HTML output.
	 */
	public function render( array $atts ): string {
		$shortcode = new Shortcode();
		return $shortcode->render( [
			'list_id'     => $atts['listId']      ?? '',
			'title'       => $atts['title']       ?? '',
			'description' => $atts['description'] ?? '',
			'button'      => $atts['button']      ?? 'Subscribe',
			'placeholder' => $atts['placeholder'] ?? 'Your email address',
			'fields'      => $atts['fields']      ?? '',
			'class'       => $atts['className']   ?? '',
			'success'     => __( 'Thank you for subscribing! 🎉', 'ctaforge' ),
			'error'       => __( 'Something went wrong. Please try again.', 'ctaforge' ),
		] );
	}

	/**
	 * Registers the block editor JS asset (built from assets/js/block/).
	 */
	private function register_editor_script(): void {
		$asset_file = CTAFORGE_PLUGIN_DIR . 'assets/js/block/index.asset.php';
		$asset      = file_exists( $asset_file )
			? require $asset_file
			: [ 'dependencies' => [ 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components' ], 'version' => CTAFORGE_VERSION ];

		wp_register_script(
			'ctaforge-block-editor',
			CTAFORGE_PLUGIN_URL . 'assets/js/block/index.js',
			$asset['dependencies'],
			$asset['version'],
			true
		);

		wp_register_style(
			'ctaforge-block-editor-style',
			CTAFORGE_PLUGIN_URL . 'assets/css/form.css',
			[],
			CTAFORGE_VERSION
		);
	}
}
