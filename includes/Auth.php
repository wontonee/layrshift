<?php
/**
 * Authentication and permission checks.
 *
 * @package LayrShift
 */

declare( strict_types=1 );

namespace LayrShift;

/**
 * Auth layer for MCP transport and abilities.
 */
final class Auth {

	public static function check_mcp_transport_permission( \WP_REST_Request $request ): bool {
		if ( ! Plugin::is_abilities_enabled() ) {
			return false;
		}

		if ( ! self::is_https_ok() ) {
			return false;
		}

		if ( ! is_user_logged_in() ) {
			return false;
		}

		return self::current_user_allowed();
	}

	public static function check_ability_permission(): bool {
		if ( ! Plugin::is_abilities_enabled() ) {
			return false;
		}

		if ( ! self::is_https_ok() ) {
			return false;
		}

		if ( ! is_user_logged_in() ) {
			return false;
		}

		return self::current_user_allowed();
	}

	/**
	 * Tighten mcp-adapter meta-ability gates to administrator-level access.
	 *
	 * @param string $capability Default capability from mcp-adapter.
	 */
	public static function mcp_adapter_capability( string $capability ): string {
		unset( $capability );

		return 'manage_options';
	}

	public static function current_user_allowed(): bool {
		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			return false;
		}

		if ( user_can( $user_id, 'manage_options' ) ) {
			$settings = Plugin::get_settings();
			$allowed  = $settings['allowed_user_ids'] ?? array();

			if ( empty( $allowed ) ) {
				return true;
			}

			return in_array( $user_id, array_map( 'intval', (array) $allowed ), true );
		}

		return false;
	}

	public static function is_https_ok(): bool {
		$settings = Plugin::get_settings();
		if ( empty( $settings['https_enforcement'] ) ) {
			return true;
		}

		return is_ssl();
	}

	public static function is_https_required_and_missing(): bool {
		$settings = Plugin::get_settings();
		return ! empty( $settings['https_enforcement'] ) && ! is_ssl();
	}

	/**
	 * @return array<int, string>
	 */
	public static function get_requirement_errors(): array {
		global $wp_version;

		$errors = array();

		if ( version_compare( $wp_version, '6.9', '<' ) ) {
			$errors[] = sprintf(
				/* translators: %s: required WordPress version */
				__( 'WordPress 6.9 or higher is required (current: %s).', 'layrshift' ),
				$wp_version
			);
		}

		if ( ! function_exists( 'wp_register_ability' ) ) {
			$errors[] = __( 'WordPress Abilities API is not available.', 'layrshift' );
		}

		if ( ! class_exists( \WP\MCP\Core\McpAdapter::class ) ) {
			$errors[] = __( 'MCP Adapter dependency is missing. Run composer install.', 'layrshift' );
		}

		if ( version_compare( PHP_VERSION, '8.0', '<' ) ) {
			$errors[] = sprintf(
				/* translators: %s: current PHP version */
				__( 'PHP 8.0 or higher is required (current: %s).', 'layrshift' ),
				PHP_VERSION
			);
		}

		if ( self::is_https_required_and_missing() ) {
			$errors[] = __( 'HTTPS is required but this site is not served over SSL.', 'layrshift' );
		}

		return $errors;
	}
}
