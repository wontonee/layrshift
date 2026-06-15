<?php
/**
 * Application Password availability helpers (Wordfence and similar plugins).
 *
 * @package LayrShift
 */

declare( strict_types=1 );

namespace LayrShift;

final class ApplicationPasswords {

	public static function init(): void {
		add_filter( 'wp_is_application_passwords_available', array( self::class, 'filter_available' ), 999 );
		add_filter( 'wp_is_application_passwords_available_for_user', array( self::class, 'filter_available_for_user' ), 999, 2 );
	}

	public static function filter_available( bool $available ): bool {
		if ( $available || ! self::should_override_block() ) {
			return $available;
		}

		return wp_is_application_passwords_supported();
	}

	/**
	 * @param bool|\WP_User $user User object or availability flag from core.
	 */
	public static function filter_available_for_user( bool $available, $user ): bool {
		if ( $available || ! self::should_override_block() ) {
			return $available;
		}

		if ( ! wp_is_application_passwords_supported() ) {
			return false;
		}

		if ( $user instanceof \WP_User ) {
			return user_can( $user, 'manage_options' );
		}

		$user_id = is_numeric( $user ) ? (int) $user : 0;
		if ( $user_id <= 0 ) {
			return false;
		}

		return user_can( $user_id, 'manage_options' );
	}

	public static function should_override_block(): bool {
		if ( ! Plugin::is_abilities_enabled() ) {
			return false;
		}

		$settings = Plugin::get_settings();

		return ! empty( $settings['enable_application_passwords'] );
	}

	/**
	 * @return string|null wordfence|solid_security|aios|generic
	 */
	public static function detect_blocker(): ?string {
		if ( defined( 'WORDFENCE_VERSION' ) || class_exists( 'wordfence', false ) ) {
			return 'wordfence';
		}

		if ( defined( 'ITSEC_VERSION' ) || defined( 'ITSEC_CORE' ) ) {
			return 'solid_security';
		}

		if ( defined( 'AIOWPSEC_VERSION' ) ) {
			return 'aios';
		}

		return 'generic';
	}

	/**
	 * @return list<string>
	 */
	public static function get_help_steps( ?string $blocker = null ): array {
		$blocker = $blocker ?? self::detect_blocker();

		if ( 'wordfence' === $blocker ) {
			return array(
				__( 'Open Wordfence → All Options (click Expand All if sections are collapsed).', 'layrshift' ),
				__( 'Under Brute Force Protection, uncheck “Disable WordPress application passwords”.', 'layrshift' ),
				__( 'Save changes, then reload this page.', 'layrshift' ),
			);
		}

		if ( 'solid_security' === $blocker ) {
			return array(
				__( 'Open Solid Security → Settings.', 'layrshift' ),
				__( 'Find the Application Passwords or REST API hardening option and allow Application Passwords.', 'layrshift' ),
				__( 'Save changes, then reload this page.', 'layrshift' ),
			);
		}

		if ( 'aios' === $blocker ) {
			return array(
				__( 'Open All-In-One Security → Settings.', 'layrshift' ),
				__( 'Allow WordPress Application Passwords if the plugin provides that option.', 'layrshift' ),
				__( 'Save changes, then reload this page.', 'layrshift' ),
			);
		}

		return array(
			__( 'Check your security plugin settings for an option that disables WordPress Application Passwords.', 'layrshift' ),
			__( 'Or enable “Allow Application Passwords” under LayrShift → Configuration → Settings (administrators only).', 'layrshift' ),
		);
	}

	/**
	 * @return array{available: bool, reason: string, message: string, blocker: string|null, help_steps: list<string>}
	 */
	public static function get_status(): array {
		if ( wp_is_application_passwords_available() ) {
			return array(
				'available'   => true,
				'reason'      => 'available',
				'message'     => '',
				'blocker'     => null,
				'help_steps'  => array(),
			);
		}

		if ( ! wp_is_application_passwords_supported() ) {
			return array(
				'available'   => false,
				'reason'      => 'unsupported',
				'message'     => __( 'Application Passwords require HTTPS or WP_ENVIRONMENT_TYPE set to "local".', 'layrshift' ),
				'blocker'     => null,
				'help_steps'  => array(),
			);
		}

		$blocker = self::detect_blocker();
		$message = match ( $blocker ) {
			'wordfence'       => __( 'Wordfence has disabled Application Passwords on this site.', 'layrshift' ),
			'solid_security'  => __( 'Solid Security has disabled Application Passwords on this site.', 'layrshift' ),
			'aios'            => __( 'All-In-One Security has disabled Application Passwords on this site.', 'layrshift' ),
			default           => __( 'Application Passwords have been disabled on this site, likely by a security plugin.', 'layrshift' ),
		};

		return array(
			'available'   => false,
			'reason'      => 'filtered',
			'message'     => $message,
			'blocker'     => $blocker,
			'help_steps'  => self::get_help_steps( $blocker ),
		);
	}
}
