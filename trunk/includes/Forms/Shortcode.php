<?php
/**
 * [ctaforge_form] shortcode — embeds a signup form anywhere.
 *
 * Usage:
 *   [ctaforge_form list_id="uuid" title="Receba novidades" button="Inscrever-me"]
 *   [ctaforge_form list_id="uuid" fields="first_name,last_name"]
 *
 * @package CTAForge\Forms
 */

namespace CTAForge\Forms;

/**
 * Registers and renders the [ctaforge_form] shortcode.
 */
class Shortcode {

	/**
	 * Constructor — registers WordPress hooks.
	 */
	public function __construct() {
		add_shortcode( 'ctaforge_form', array( $this, 'render' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'wp_ajax_ctaforge_subscribe', array( $this, 'handle_ajax' ) );
		add_action( 'wp_ajax_nopriv_ctaforge_subscribe', array( $this, 'handle_ajax' ) );
	}

	/**
	 * Renders the signup form HTML.
	 *
	 * @param  array $atts Shortcode attributes.
	 * @return string       HTML output.
	 */
	public function render( array $atts ): string {
		$atts = shortcode_atts(
			array(
				'list_id'     => '',
				'title'       => __( 'Subscribe to our newsletter', 'ctaforge' ),
				'description' => '',
				'button'      => __( 'Subscribe', 'ctaforge' ),
				'placeholder' => __( 'Your email address', 'ctaforge' ),
				'fields'      => '',        // Comma-separated extra fields: first_name, last_name.
				'success'     => __( 'Thank you for subscribing! 🎉', 'ctaforge' ),
				'error'       => __( 'Something went wrong. Please try again.', 'ctaforge' ),
				'class'       => '',
			),
			$atts,
			'ctaforge_form'
		);

		// Resolve list_id: attribute → plugin default.
		$settings = get_option( 'ctaforge_settings', array() );
		$list_id  = '' !== $atts['list_id'] ? $atts['list_id'] : ( $settings['default_list'] ?? '' );

		if ( empty( $list_id ) ) {
			if ( current_user_can( 'manage_options' ) ) {
				return '<p class="ctaforge-notice">'
					. esc_html__( 'CTAForge: set a list_id in the shortcode or configure a default list in Settings.', 'ctaforge' )
					. '</p>';
			}
			return '';
		}

		$extra_fields = array_filter( array_map( 'trim', explode( ',', $atts['fields'] ) ) );
		$form_id      = 'ctaforge-form-' . wp_rand( 1000, 9999 );

		ob_start();
		?>
		<div
			class="ctaforge-form-wrap <?php echo esc_attr( $atts['class'] ); ?>"
			id="<?php echo esc_attr( $form_id ); ?>"
		>
			<?php if ( $atts['title'] ) : ?>
				<h3 class="ctaforge-form-title"><?php echo esc_html( $atts['title'] ); ?></h3>
			<?php endif; ?>

			<?php if ( $atts['description'] ) : ?>
				<p class="ctaforge-form-desc"><?php echo esc_html( $atts['description'] ); ?></p>
			<?php endif; ?>

			<form
				class="ctaforge-form"
				data-list-id="<?php echo esc_attr( $list_id ); ?>"
				data-success="<?php echo esc_attr( $atts['success'] ); ?>"
				data-error="<?php echo esc_attr( $atts['error'] ); ?>"
				novalidate
			>
				<?php if ( in_array( 'first_name', $extra_fields, true ) ) : ?>
					<div class="ctaforge-field">
						<label for="<?php echo esc_attr( $form_id ); ?>-first_name">
							<?php esc_html_e( 'First name', 'ctaforge' ); ?>
						</label>
						<input
							type="text"
							id="<?php echo esc_attr( $form_id ); ?>-first_name"
							name="first_name"
							autocomplete="given-name"
						/>
					</div>
				<?php endif; ?>

				<?php if ( in_array( 'last_name', $extra_fields, true ) ) : ?>
					<div class="ctaforge-field">
						<label for="<?php echo esc_attr( $form_id ); ?>-last_name">
							<?php esc_html_e( 'Last name', 'ctaforge' ); ?>
						</label>
						<input
							type="text"
							id="<?php echo esc_attr( $form_id ); ?>-last_name"
							name="last_name"
							autocomplete="family-name"
						/>
					</div>
				<?php endif; ?>

				<div class="ctaforge-field ctaforge-field--email">
					<label for="<?php echo esc_attr( $form_id ); ?>-email">
						<?php esc_html_e( 'Email', 'ctaforge' ); ?>
					</label>
					<input
						type="email"
						id="<?php echo esc_attr( $form_id ); ?>-email"
						name="email"
						placeholder="<?php echo esc_attr( $atts['placeholder'] ); ?>"
						required
						autocomplete="email"
					/>
				</div>

				<?php wp_nonce_field( 'ctaforge_subscribe', '_ctaforge_nonce' ); ?>

				<button type="submit" class="ctaforge-submit">
					<span class="ctaforge-submit-label"><?php echo esc_html( $atts['button'] ); ?></span>
					<span class="ctaforge-submit-loading" aria-hidden="true" style="display:none;">
						<?php esc_html_e( 'Sending…', 'ctaforge' ); ?>
					</span>
				</button>

				<div class="ctaforge-message" role="alert" aria-live="polite"></div>
			</form>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Handles the AJAX subscription request from the form.
	 */
	public function handle_ajax(): void {
		check_ajax_referer( 'ctaforge_subscribe', '_ctaforge_nonce' );

		$email      = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
		$list_id    = sanitize_text_field( wp_unslash( $_POST['list_id'] ?? '' ) );
		$first_name = sanitize_text_field( wp_unslash( $_POST['first_name'] ?? '' ) );
		$last_name  = sanitize_text_field( wp_unslash( $_POST['last_name'] ?? '' ) );

		if ( ! is_email( $email ) ) {
			wp_send_json_error( array( 'message' => __( 'Please enter a valid email address.', 'ctaforge' ) ), 422 );
		}

		if ( empty( $list_id ) ) {
			wp_send_json_error( array( 'message' => __( 'No list configured.', 'ctaforge' ) ), 400 );
		}

		$fields = array();
		if ( $first_name ) {
			$fields['firstName'] = $first_name;
		}
		if ( $last_name ) {
			$fields['lastName'] = $last_name;
		}

		$client = \CTAForge\Api\Client::make();
		$result = $client->subscribe( $email, $list_id, $fields );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ), 500 );
		}

		// Track the form_submitted event so the activity appears
		// in the contact's timeline in CTAForge.
		$client->track_event(
			$email,
			'form_submitted',
			array(
				'list_id'  => $list_id,
				'page_url' => ( false !== wp_get_referer() ? wp_get_referer() : '' ),
				'source'   => 'shortcode',
			)
		);
		// Ignore track_event errors — subscription already succeeded.

		wp_send_json_success( array( 'contact' => $result ) );
	}

	/**
	 * Enqueues front-end CSS and JS.
	 */
	public function enqueue_assets(): void {
		wp_enqueue_style(
			'ctaforge-form',
			CTAFORGE_PLUGIN_URL . 'assets/css/form.css',
			array(),
			CTAFORGE_VERSION
		);

		wp_enqueue_script(
			'ctaforge-form',
			CTAFORGE_PLUGIN_URL . 'assets/js/form.js',
			array( 'jquery' ),
			CTAFORGE_VERSION,
			true
		);

		wp_localize_script(
			'ctaforge-form',
			'ctaforgeAjax',
			array(
				'url'   => admin_url( 'admin-ajax.php' ),
				'nonce' => wp_create_nonce( 'ctaforge_subscribe' ),
			)
		);
	}
}
