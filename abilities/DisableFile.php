<?php
/**
 * disable_file ability.
 *
 * @package LayrShift
 */

declare( strict_types=1 );

namespace LayrShift\Abilities;

use LayrShift\Sandbox;

final class DisableFile {

	use AbilityTrait;

	/**
	 * @param array<string, mixed> $input
	 * @return array<string, mixed>|\WP_Error
	 */
	public static function execute( array $input ) {
		return self::run_logged(
			'layrshift/disable-file',
			$input,
			static function () use ( $input ) {
				$filename = basename( (string) ( $input['filename'] ?? '' ) );
				if ( '' === $filename ) {
					return new \WP_Error( 'layrshift_empty_filename', __( 'filename is required.', 'layrshift' ) );
				}

				$result = Sandbox::disable_file( $filename );
				if ( is_wp_error( $result ) ) {
					return $result;
				}

				return array(
					'success'       => true,
					'disabled_path' => Sandbox::get_directory() . '/' . $filename . '.disabled',
				);
			}
		);
	}
}
