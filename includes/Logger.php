<?php
/**
 * Ability invocation logger.
 *
 * @package LayrShift
 */

declare( strict_types=1 );

namespace LayrShift;

/**
 * Rolling log of ability invocations.
 */
final class Logger {

	private const OPTION_KEY = 'layrshift_ability_log';
	private const MAX_ENTRIES = 500;

	/**
	 * @param array<string, mixed> $input
	 * @param mixed                $output
	 */
	public static function log( string $ability, array $input, $output, bool $success, float $duration_ms ): void {
		$entries = get_option( self::OPTION_KEY, array() );
		if ( ! is_array( $entries ) ) {
			$entries = array();
		}

		$entries[] = array(
			'timestamp'        => gmdate( 'c' ),
			'ability'          => $ability,
			'user_id'          => get_current_user_id(),
			'user_login'       => wp_get_current_user()->user_login ?? '',
			'ip'               => self::get_client_ip(),
			'execution_time_ms'=> round( $duration_ms, 2 ),
			'status'           => $success ? 'success' : 'error',
			'input'            => self::truncate( wp_json_encode( $input ) ?: '' ),
			'output'           => self::truncate( is_string( $output ) ? $output : ( wp_json_encode( $output ) ?: '' ) ),
		);

		if ( count( $entries ) > self::MAX_ENTRIES ) {
			$entries = array_slice( $entries, -self::MAX_ENTRIES );
		}

		update_option( self::OPTION_KEY, $entries, false );
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_entries(): array {
		$entries = get_option( self::OPTION_KEY, array() );
		return is_array( $entries ) ? array_reverse( $entries ) : array();
	}

	public static function clear(): void {
		delete_option( self::OPTION_KEY );
	}

	private static function truncate( string $value, int $max = 2000 ): string {
		if ( strlen( $value ) <= $max ) {
			return $value;
		}
		return substr( $value, 0, $max ) . '…';
	}

	private static function get_client_ip(): string {
		if ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
			return sanitize_text_field( wp_unslash( (string) $_SERVER['REMOTE_ADDR'] ) );
		}
		return '';
	}
}
