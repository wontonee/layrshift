<?php
/**
 * execute_php ability.
 *
 * @package LayrShift
 */

declare( strict_types=1 );

namespace LayrShift\Abilities;

use LayrShift\Plugin;

final class ExecutePhp {

	use AbilityTrait;

	/**
	 * @param array<string, mixed> $input
	 * @return array<string, mixed>|\WP_Error
	 */
	public static function execute( array $input ) {
		return self::run_logged(
			'layrshift/execute-php',
			$input,
			static function () use ( $input ) {
				$code = $input['code'] ?? '';
				if ( ! is_string( $code ) || '' === trim( $code ) ) {
					return new \WP_Error( 'layrshift_empty_code', __( 'Code cannot be empty.', 'layrshift' ) );
				}

				$settings = Plugin::get_settings();
				$limit    = max( 5, min( 120, (int) ( $settings['exec_time_limit'] ?? 30 ) ) );
				@set_time_limit( $limit );

				global $wpdb;

				ob_start();
				$error  = null;
				$return = null;

				try {
					$return = eval( $code ); // phpcs:ignore Generic.PHP.ForbiddenFunctions.Found -- Core execute-php ability.
				} catch ( \Throwable $e ) {
					$error = $e->getMessage();
				}

				$output = ob_get_clean();
				if ( false === $output ) {
					$output = '';
				}

				return array(
					'output'            => $output,
					'return_value'      => $return,
					'error'             => $error,
					'execution_time_ms' => 0,
				);
			}
		);
	}
}
