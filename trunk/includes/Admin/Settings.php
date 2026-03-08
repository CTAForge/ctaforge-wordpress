<?php
/**
 * Admin settings page — API key, endpoint and default list.
 *
 * @package CTAForge\Admin
 */

namespace CTAForge\Admin;

/**
 * Renders and saves the plugin settings form.
 */
class Settings {

	/** Option key used to store all plugin settings. */
	const OPTION_KEY = 'ctaforge_settings';

	public function __construct() {
		add_action( 'admin_init', [ $this, 'register_settings' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );

		// Test Connection AJAX — authenticated (admin only, server-side only).
		// The API key never travels from the server to the browser;
		// the test is executed entirely in PHP and only the result is returned.
		add_action( 'wp_ajax_ctaforge_test_connection', [ $this, 'handle_test_connection' ] );
	}

	/**
	 * Registers the settings, sections and fields via the Settings API.
	 */
	public function register_settings(): void {
		register_setting(
			'ctaforge_settings_group',
			self::OPTION_KEY,
			[ 'sanitize_callback' => [ $this, 'sanitize' ] ]
		);

		// ── Section: Connection ───────────────────────────────────────────────
		add_settings_section(
			'ctaforge_connection',
			__( 'API Connection', 'ctaforge' ),
			function (): void {
				echo '<p>' . esc_html__( 'Enter your CTAForge API key. You can find it in CTAForge → Settings → API Keys.', 'ctaforge' ) . '</p>';
			},
			'ctaforge'
		);

		add_settings_field(
			'api_key',
			__( 'API Key', 'ctaforge' ),
			[ $this, 'field_api_key' ],
			'ctaforge',
			'ctaforge_connection'
		);

		add_settings_field(
			'api_url',
			__( 'API Endpoint', 'ctaforge' ),
			[ $this, 'field_api_url' ],
			'ctaforge',
			'ctaforge_connection'
		);

		// ── Section: Defaults ─────────────────────────────────────────────────
		add_settings_section(
			'ctaforge_defaults',
			__( 'Defaults', 'ctaforge' ),
			function (): void {
				echo '<p>' . esc_html__( 'Configure default behaviour for CTAForge forms on this site.', 'ctaforge' ) . '</p>';
			},
			'ctaforge'
		);

		add_settings_field(
			'default_list',
			__( 'Default List', 'ctaforge' ),
			[ $this, 'field_default_list' ],
			'ctaforge',
			'ctaforge_defaults'
		);

		add_settings_field(
			'sync_users',
			__( 'Sync WordPress Users', 'ctaforge' ),
			[ $this, 'field_sync_users' ],
			'ctaforge',
			'ctaforge_defaults'
		);
	}

	/** Renders the API key input. */
	public function field_api_key(): void {
		$settings = get_option( self::OPTION_KEY, [] );
		$value    = $settings['api_key'] ?? '';
		?>
		<input
			type="password"
			id="ctaforge_api_key"
			name="<?php echo esc_attr( self::OPTION_KEY ); ?>[api_key]"
			value="<?php echo esc_attr( $value ); ?>"
			class="regular-text"
			autocomplete="off"
			placeholder="ctaf_…"
		/>
		<button type="button" class="button ctaforge-test-connection" data-nonce="<?php echo esc_attr( wp_create_nonce( 'ctaforge_test_connection' ) ); ?>">
			<?php esc_html_e( 'Test Connection', 'ctaforge' ); ?>
		</button>
		<span class="ctaforge-connection-status" style="margin-left:8px;"></span>
		<?php
	}

	/** Renders the API endpoint input. */
	public function field_api_url(): void {
		$settings = get_option( self::OPTION_KEY, [] );
		$value    = $settings['api_url'] ?? CTAFORGE_API_DEFAULT;
		?>
		<input
			type="url"
			name="<?php echo esc_attr( self::OPTION_KEY ); ?>[api_url]"
			value="<?php echo esc_attr( $value ); ?>"
			class="regular-text"
		/>
		<p class="description">
			<?php
			printf(
				/* translators: %s: default URL */
				esc_html__( 'Leave blank to use the default (%s). Change only for self-hosted instances.', 'ctaforge' ),
				esc_html( CTAFORGE_API_DEFAULT )
			);
			?>
		</p>
		<?php
	}

	/** Renders the default list selector. */
	public function field_default_list(): void {
		$settings    = get_option( self::OPTION_KEY, [] );
		$api_key     = $settings['api_key'] ?? '';
		$default_list = $settings['default_list'] ?? '';

		if ( empty( $api_key ) ) {
			echo '<p class="description">' . esc_html__( 'Save your API key first to load available lists.', 'ctaforge' ) . '</p>';
			return;
		}

		$client = \CTAForge\Api\Client::make();
		$lists  = $client->get_lists();

		if ( is_wp_error( $lists ) || empty( $lists ) ) {
			echo '<p class="description">' . esc_html__( 'No lists found. Check your API key and try again.', 'ctaforge' ) . '</p>';
			return;
		}

		echo '<select name="' . esc_attr( self::OPTION_KEY ) . '[default_list]">';
		echo '<option value="">' . esc_html__( '— No default —', 'ctaforge' ) . '</option>';
		foreach ( $lists as $list ) {
			printf(
				'<option value="%s" %s>%s (%d subscribers)</option>',
				esc_attr( $list['id'] ),
				selected( $default_list, $list['id'], false ),
				esc_html( $list['name'] ),
				(int) $list['subscriberCount']
			);
		}
		echo '</select>';
	}

	/** Renders the sync users toggle. */
	public function field_sync_users(): void {
		$settings   = get_option( self::OPTION_KEY, [] );
		$sync_users = $settings['sync_users'] ?? '0';
		?>
		<label>
			<input
				type="checkbox"
				name="<?php echo esc_attr( self::OPTION_KEY ); ?>[sync_users]"
				value="1"
				<?php checked( '1', $sync_users ); ?>
			/>
			<?php esc_html_e( 'Automatically subscribe new WordPress users to the default list.', 'ctaforge' ); ?>
		</label>
		<?php
	}

	/**
	 * Sanitizes settings before saving.
	 *
	 * @param  array $input Raw input.
	 * @return array        Sanitized settings.
	 */
	public function sanitize( array $input ): array {
		return [
			'api_key'      => sanitize_text_field( $input['api_key'] ?? '' ),
			'api_url'      => esc_url_raw( $input['api_url'] ?? CTAFORGE_API_DEFAULT ) ?: CTAFORGE_API_DEFAULT,
			'default_list' => sanitize_text_field( $input['default_list'] ?? '' ),
			'sync_users'   => ( '1' === ( $input['sync_users'] ?? '0' ) ) ? '1' : '0',
		];
	}

	/**
	 * Handles the "Test Connection" AJAX request.
	 *
	 * Security model:
	 * - `wp_ajax_` prefix means this only runs for logged-in users
	 * - nonce verification (ctaforge_test_connection, bound to user session)
	 * - capability check (manage_options)
	 * - API key is NEVER sent to the browser — it is read from wp_options on
	 *   the server, used to call the CTAForge API, and only the result (ok/fail)
	 *   is returned to the browser as JSON
	 * - The API key field in the form is type="password" so it is not echoed
	 *   back in the JS response
	 */
	public function handle_test_connection(): void {
		// 1. Verify the nonce (CSRF protection, tied to the current WP session).
		check_ajax_referer( 'ctaforge_test_connection', '_wpnonce' );

		// 2. Capability gate — only admins can test the connection.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Insufficient permissions.', 'ctaforge' ) ], 403 );
		}

		// 3. Read the API key from wp_options (server-side only — never from $_POST).
		//    This means even if someone forges the AJAX request, they cannot
		//    substitute a different key to probe the API.
		$settings = get_option( self::OPTION_KEY, [] );
		$api_key  = $settings['api_key'] ?? '';
		$api_url  = $settings['api_url'] ?? CTAFORGE_API_DEFAULT;

		if ( empty( $api_key ) ) {
			wp_send_json_error( [ 'message' => __( 'No API key configured. Save your settings first.', 'ctaforge' ) ], 422 );
		}

		// 4. Execute a lightweight introspection query to validate the key.
		//    Uses the same Client that all API calls go through.
		$client = new \CTAForge\Api\Client( $api_key, $api_url );
		$result = $client->query(
			'query Ping { me { id email } }',
		);

		if ( is_wp_error( $result ) ) {
			wp_send_json_error(
				[ 'message' => $result->get_error_message() ],
				401
			);
		}

		// 5. Return only the tenant/user info (no key echoed back).
		wp_send_json_success( [
			'message' => __( 'Connection successful!', 'ctaforge' ),
			'user'    => $result['me'] ?? null,
		] );
	}

	/**
	 * Renders the full settings page.
	 */
	public function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<form method="post" action="options.php">
				<?php
				settings_fields( 'ctaforge_settings_group' );
				do_settings_sections( 'ctaforge' );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Enqueues admin-side assets.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_assets( string $hook ): void {
		if ( 'settings_page_ctaforge' !== $hook ) {
			return;
		}

		wp_enqueue_script(
			'ctaforge-admin',
			CTAFORGE_PLUGIN_URL . 'assets/js/admin.js',
			[ 'jquery' ],
			CTAFORGE_VERSION,
			true
		);

		wp_localize_script( 'ctaforge-admin', 'ctaforgeAdmin', [
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			// Nonce bound to current user session (CSRF protection).
			// The API key is NOT included here — it stays server-side.
			'nonce'   => wp_create_nonce( 'ctaforge_test_connection' ),
			'i18n'    => [
				'testing'    => __( 'Testing…', 'ctaforge' ),
				'testBtn'    => __( 'Test Connection', 'ctaforge' ),
				'connected'  => __( '✅ Connected', 'ctaforge' ),
				'error'      => __( '❌ Connection failed', 'ctaforge' ),
				'saveFirst'  => __( '⚠️ Save your settings before testing.', 'ctaforge' ),
			],
		] );
	}
}
