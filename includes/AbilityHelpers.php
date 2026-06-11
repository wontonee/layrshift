<?php
/**
 * Shared helpers for abilities and Gutenberg queue.
 *
 * @package LayrShift
 */

declare( strict_types=1 );

namespace LayrShift;

/**
 * Cross-cutting ability helpers (ported from Novamira patterns).
 */
final class AbilityHelpers {

	public static function current_user_can_manage(): bool {
		return Auth::check_ability_permission();
	}

	public static function user_can_manage( int|\WP_User $user ): bool {
		if ( $user instanceof \WP_User ) {
			$user_id = (int) $user->ID;
		} else {
			$user_id = $user;
		}

		if ( $user_id <= 0 ) {
			return false;
		}

		if ( ! user_can( $user_id, 'manage_options' ) ) {
			return false;
		}

		$settings = Plugin::get_settings();
		$allowed  = $settings['allowed_user_ids'] ?? array();
		if ( empty( $allowed ) ) {
			return true;
		}

		return in_array( $user_id, array_map( 'intval', (array) $allowed ), true );
	}

	public static function rest_header_token( \WP_REST_Request $request, string $header_name ): string {
		$value = $request->get_header( $header_name );
		if ( ! is_string( $value ) ) {
			return '';
		}

		return trim( $value );
	}
}
