<?php
/**
 * Crash recovery / safe mode.
 *
 * @package LayrShift
 */

declare( strict_types=1 );

namespace LayrShift;

/**
 * Safe mode handler for broken sandbox files.
 */
final class CrashRecovery {

	public static function init(): void {
		add_action( 'plugins_loaded', array( self::class, 'maybe_enable_safe_mode' ), 0 );
		add_action( 'admin_notices', array( self::class, 'render_safe_mode_notice' ) );
	}

	public static function maybe_enable_safe_mode(): void {
		if ( ! is_admin() ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( empty( $_GET['layrshift-safe-mode'] ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		Sandbox::enable_safe_mode();
	}
	
	public static function render_safe_mode_notice(): void {
		if ( ! Sandbox::is_safe_mode_active() ) {
			return;
		}

		echo '<div class="notice notice-warning"><p><strong>';
		esc_html_e( 'LayrShift Safe Mode is active.', 'layrshift' );
		echo '</strong> ';
		esc_html_e( 'Sandbox PHP files are not being loaded. Review files under LayrShift → Sandbox, then exit safe mode.', 'layrshift' );
		echo '</p></div>';
	}
}
