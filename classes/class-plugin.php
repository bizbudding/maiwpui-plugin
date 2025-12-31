<?php
/**
 * MaiWPUI Plugin class.
 *
 * Handles plugin initialization, meta registration, and version upgrades.
 *
 * @since 0.1.0
 *
 * @package MaiWPUI
 */

namespace MaiWPUI;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Plugin class.
 *
 * @since 0.1.0
 */
class Plugin {

	/**
	 * Option name for plugin version.
	 *
	 * @since 0.1.0
	 */
	const VERSION_OPTION = 'maiwpui_version';

	/**
	 * Option name for database version.
	 *
	 * @since 0.1.0
	 */
	const DB_VERSION_OPTION = 'maiwpui_db_version';

	/**
	 * Current database version.
	 *
	 * Increment this when you need to run a migration.
	 *
	 * @since 0.1.0
	 */
	const DB_VERSION = 1;

	/**
	 * Instance.
	 *
	 * @since 0.1.0
	 *
	 * @var Plugin|null
	 */
	private static ?Plugin $instance = null;

	/**
	 * Get instance.
	 *
	 * @since 0.1.0
	 *
	 * @return Plugin
	 */
	public static function get_instance(): Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 */
	private function __construct() {
		$this->hooks();
	}

	/**
	 * Register hooks.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	private function hooks(): void {
		add_action( 'init', [ $this, 'register_meta' ] );
		add_action( 'init', [ $this, 'maybe_upgrade' ], 5 );
	}

	/**
	 * Register user meta keys.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function register_meta(): void {
		// Auth tokens meta (private, not exposed to REST).
		register_meta(
			'user',
			Auth::TOKEN_META_KEY,
			[
				'type'              => 'array',
				'description'       => __( 'Authentication tokens for API access.', 'maiwpui' ),
				'single'            => true,
				'sanitize_callback' => [ $this, 'sanitize_tokens_meta' ],
				'auth_callback'     => '__return_false', // Never expose via REST.
				'show_in_rest'      => false,
			]
		);

		/**
		 * Filter the user meta keys to register.
		 *
		 * Use this to register additional meta keys that the plugin should manage.
		 *
		 * @since 0.1.0
		 *
		 * @param array $meta_keys Array of meta key definitions.
		 *                         Each key should have: type, description, sanitize_callback (optional).
		 */
		$custom_meta = apply_filters( 'maiwpui_register_user_meta', [] );

		foreach ( $custom_meta as $meta_key => $args ) {
			$defaults = [
				'type'              => 'string',
				'description'       => '',
				'single'            => true,
				'sanitize_callback' => 'sanitize_text_field',
				'auth_callback'     => '__return_false',
				'show_in_rest'      => false,
			];

			register_meta( 'user', $meta_key, wp_parse_args( $args, $defaults ) );
		}
	}

	/**
	 * Sanitize tokens meta.
	 *
	 * @since 0.1.0
	 *
	 * @param mixed $value The value to sanitize.
	 *
	 * @return array
	 */
	public function sanitize_tokens_meta( $value ): array {
		if ( ! is_array( $value ) ) {
			return [];
		}

		$sanitized = [];

		foreach ( $value as $selector => $token_data ) {
			// Validate selector format (16 hex chars).
			if ( ! preg_match( '/^[a-f0-9]{16}$/', $selector ) ) {
				continue;
			}

			if ( ! is_array( $token_data ) ) {
				continue;
			}

			// Validate required fields.
			if ( empty( $token_data['hash'] ) || empty( $token_data['expires'] ) ) {
				continue;
			}

			$sanitized[ $selector ] = [
				'hash'    => sanitize_text_field( $token_data['hash'] ),
				'created' => absint( $token_data['created'] ?? time() ),
				'expires' => absint( $token_data['expires'] ),
				'device'  => sanitize_text_field( $token_data['device'] ?? '' ),
			];
		}

		return $sanitized;
	}

	/**
	 * Check if upgrade is needed and run migrations.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function maybe_upgrade(): void {
		$current_version    = get_option( self::VERSION_OPTION, '0.0.0' );
		$current_db_version = (int) get_option( self::DB_VERSION_OPTION, 0 );

		// Check if this is a fresh install or upgrade.
		$is_fresh_install = '0.0.0' === $current_version;

		// Run DB migrations if needed.
		if ( $current_db_version < self::DB_VERSION ) {
			$this->run_migrations( $current_db_version, $is_fresh_install );
		}

		// Update version options if changed.
		if ( MAIWPUI_VERSION !== $current_version ) {
			update_option( self::VERSION_OPTION, MAIWPUI_VERSION, false ); // false = no autoload.
		}

		if ( $current_db_version < self::DB_VERSION ) {
			update_option( self::DB_VERSION_OPTION, self::DB_VERSION, false ); // false = no autoload.
		}
	}

	/**
	 * Run database migrations.
	 *
	 * @since 0.1.0
	 *
	 * @param int  $from_version     The current DB version.
	 * @param bool $is_fresh_install Whether this is a fresh install.
	 *
	 * @return void
	 */
	private function run_migrations( int $from_version, bool $is_fresh_install ): void {
		// Skip migrations on fresh install - just set to current version.
		if ( $is_fresh_install ) {
			return;
		}

		// Run migrations sequentially.
		// Example:
		// if ( $from_version < 2 ) {
		//     $this->migrate_to_v2();
		// }
		// if ( $from_version < 3 ) {
		//     $this->migrate_to_v3();
		// }

		/**
		 * Fires after migrations have run.
		 *
		 * @since 0.1.0
		 *
		 * @param int  $from_version     The previous DB version.
		 * @param int  $to_version       The new DB version.
		 * @param bool $is_fresh_install Whether this is a fresh install.
		 */
		do_action( 'maiwpui_after_migrations', $from_version, self::DB_VERSION, $is_fresh_install );
	}

	/**
	 * Get plugin version.
	 *
	 * @since 0.1.0
	 *
	 * @return string
	 */
	public static function get_version(): string {
		return MAIWPUI_VERSION;
	}

	/**
	 * Get database version.
	 *
	 * @since 0.1.0
	 *
	 * @return int
	 */
	public static function get_db_version(): int {
		return self::DB_VERSION;
	}

	/**
	 * Plugin activation.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public static function activate(): void {
		// Set initial versions.
		add_option( self::VERSION_OPTION, MAIWPUI_VERSION, '', false );
		add_option( self::DB_VERSION_OPTION, self::DB_VERSION, '', false );

		/**
		 * Fires on plugin activation.
		 *
		 * @since 0.1.0
		 */
		do_action( 'maiwpui_activate' );
	}

	/**
	 * Plugin deactivation.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public static function deactivate(): void {
		/**
		 * Fires on plugin deactivation.
		 *
		 * @since 0.1.0
		 */
		do_action( 'maiwpui_deactivate' );
	}

	/**
	 * Plugin uninstall.
	 *
	 * Called from uninstall.php.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public static function uninstall(): void {
		// Delete options.
		delete_option( self::VERSION_OPTION );
		delete_option( self::DB_VERSION_OPTION );

		// Optionally clean up all user tokens.
		// This is aggressive - you might want to leave data intact.
		// global $wpdb;
		// $wpdb->delete( $wpdb->usermeta, [ 'meta_key' => Auth::TOKEN_META_KEY ] );

		/**
		 * Fires on plugin uninstall.
		 *
		 * @since 0.1.0
		 */
		do_action( 'maiwpui_uninstall' );
	}
}
