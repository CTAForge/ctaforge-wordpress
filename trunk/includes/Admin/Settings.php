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
			'nonce'   => wp_create_nonce( 'ctaforge_test_connection' ),
			'i18n'    => [
				'testing'    => __( 'Testing…', 'ctaforge' ),
				'connected'  => __( '✅ Connected', 'ctaforge' ),
				'error'      => __( '❌ Connection failed', 'ctaforge' ),
			],
		] );
	}
}
