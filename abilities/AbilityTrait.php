<?php
/**
 * Shared ability helpers.
 *
 * @package LayrShift
 */

declare( strict_types=1 );

namespace LayrShift\Abilities;

use LayrShift\Auth;
use LayrShift\Logger;

/**
 * Trait for ability classes.
 */
trait AbilityTrait {

	protected static function permission(): bool {
		return Auth::check_ability_permission();
	}

	/**
	 * @param array<string, mixed> $input
	 * @param callable             $callback
	 * @return mixed|\WP_Error
	 */
	protected static function run_logged( string $name, array $input, callable $callback ) {
		$start = microtime( true );
		$result = $callback();
		$duration = ( microtime( true ) - $start ) * 1000;
		$success = ! is_wp_error( $result );

		Logger::log( $name, $input, $success ? $result : $result->get_error_message(), $success, $duration );

		return $result;
	}
}
