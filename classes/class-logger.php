<?php
/**
 * Logger class.
 *
 * Provides logging functionality for debugging and error tracking.
 * For now, this file must exist in /plugins/{plugin-name}/classes/class-logger.php.
 *
 * @version 0.5.0
 *
 * @package MaiExpoWP
 */

namespace MaiExpoWP;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Logger class.
 *
 * @since 0.1.0
 */
class Logger {

	/**
	 * The singleton instance.
	 *
	 * @since 0.1.0
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * The plugin name.
	 *
	 * @since 0.1.0
	 *
	 * @var string
	 */
	private string $plugin_name = '';

	/**
	 * Get the singleton instance.
	 *
	 * @since 0.1.0
	 *
	 * @return self
	 */
	public static function get_instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Private constructor to prevent direct instantiation.
	 *
	 * @since 0.1.0
	 */
	private function __construct() {}

	/**
	 * Log a message.
	 *
	 * @since 0.1.0
	 *
	 * @param string $message The message to log.
	 * @param string $type    The type of message (error, warning, info, success).
	 * @param mixed  ...$args Additional arguments to log/dump.
	 *
	 * @return void
	 */
	private function log( string $message, string $type = 'error', ...$args ): void {
		// Always log errors, for other types only log if debugging is enabled.
		if ( 'error' !== $type && ( ! defined( 'WP_DEBUG' ) || ! \WP_DEBUG ) ) {
			return;
		}

		// Lazy initialize plugin name only when actually logging.
		if ( ! $this->plugin_name ) {
			$this->set_plugin_name();
		}

		// Format the message.
		$formatted_message = sprintf( '%s [%s]: %s', $this->plugin_name, strtoupper( $type ), $message );

		// Format additional arguments if provided.
		$formatted_args = $this->format_args( ...$args );

		// Format the final message.
		$formatted_full = trim( $formatted_message . ' ' . $formatted_args );

		// If displaying.
		if ( defined( 'WP_DEBUG_DISPLAY' ) && \WP_DEBUG_DISPLAY ) {
			// If ray is available, use it for additional debugging.
			if ( function_exists( 'ray' ) ) {
				/** @disregard P1010 */
				\ray( $formatted_message )->label( $this->plugin_name );
				// Also dump the additional args if provided.
				if ( ! empty( $args ) ) {
					/** @disregard P1010 */
					\ray( ...$args )->label( $this->plugin_name );
				}
			}
		}

		// If running in WP-CLI, output directly to console.
		if ( defined( 'WP_CLI' ) && constant( 'WP_CLI' ) ) {
			switch ( $type ) {
				case 'error':
					/** @disregard P1009 */
					\WP_CLI::error( $formatted_full, false ); // false prevents exit.
					break;
				case 'success':
					/** @disregard P1009 */
					\WP_CLI::success( $formatted_full );
					break;
				case 'warning':
					/** @disregard P1009 */
					\WP_CLI::warning( $formatted_full );
					break;
				default:
					/** @disregard P1009 */
					\WP_CLI::log( $formatted_full );
					break;
			}

			// If running via CLI, we don't want to clog the log file. We'll see logs in the console.
			return;
		}

		// If logging.
		if ( defined( 'WP_DEBUG_LOG' ) && \WP_DEBUG_LOG ) {
			// Log the message with additional args.
			error_log( $formatted_full );
		}
	}

	/**
	 * Log an error message.
	 * Always logs regardless of debug settings.
	 *
	 * @since 0.1.0
	 *
	 * @param string $message The message to log.
	 * @param mixed  ...$args Additional arguments to log/dump.
	 *
	 * @return void
	 */
	public function error( string $message, ...$args ): void {
		$this->log( $message, 'error', ...$args );
	}

	/**
	 * Log a warning message.
	 * Only logs if debugging is enabled.
	 *
	 * @since 0.1.0
	 *
	 * @param string $message The message to log.
	 * @param mixed  ...$args Additional arguments to log/dump.
	 *
	 * @return void
	 */
	public function warning( string $message, ...$args ): void {
		$this->log( $message, 'warning', ...$args );
	}

	/**
	 * Log a success message.
	 * Only logs if debugging is enabled.
	 *
	 * @since 0.1.0
	 *
	 * @param string $message The message to log.
	 * @param mixed  ...$args Additional arguments to log/dump.
	 *
	 * @return void
	 */
	public function success( string $message, ...$args ): void {
		$this->log( $message, 'success', ...$args );
	}

	/**
	 * Log an info message.
	 * Only logs if debugging is enabled.
	 *
	 * @since 0.1.0
	 *
	 * @param string $message The message to log.
	 * @param mixed  ...$args Additional arguments to log/dump.
	 *
	 * @return void
	 */
	public function info( string $message, ...$args ): void {
		$this->log( $message, 'info', ...$args );
	}

	/**
	 * Set the plugin name.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	private function set_plugin_name(): void {
		$this->plugin_name = plugin_basename( dirname( __DIR__ ) );
	}

	/**
	 * Format additional arguments for logging.
	 *
	 * @since 0.4.0
	 *
	 * @param mixed ...$args The arguments to format.
	 *
	 * @return string
	 */
	private function format_args( ...$args ): string {
		if ( empty( $args ) ) {
			return '';
		}

		$formatted = [];
		foreach ( $args as $arg ) {
			if ( is_int( $arg ) || is_float( $arg ) ) {
				$formatted[] = (string) $arg;
			} elseif ( is_string( $arg ) ) {
				$formatted[] = $arg;
			} elseif ( is_array( $arg ) || is_object( $arg ) ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
				$formatted[] = print_r( $arg, true );
			} elseif ( is_bool( $arg ) ) {
				$formatted[] = $arg ? 'true' : 'false';
			} elseif ( is_null( $arg ) ) {
				$formatted[] = 'null';
			} else {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
				$formatted[] = gettype( $arg ) . ': ' . print_r( $arg, true );
			}
		}

		return ! empty( $formatted ) ? "\n" . implode( "\n", $formatted ) : '';
	}
}
