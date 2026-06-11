<?php
/**
 * write_file ability.
 *
 * @package LayrShift
 */

declare( strict_types=1 );

namespace LayrShift\Abilities;

use LayrShift\PathHelper;
use LayrShift\Sandbox;

final class WriteFile {

	use AbilityTrait;

	/**
	 * @param array<string, mixed> $input
	 * @return array<string, mixed>|\WP_Error
	 */
	public static function execute( array $input ) {
		return self::run_logged(
			'layrshift/write-file',
			$input,
			static function () use ( $input ) {
				$path = PathHelper::resolve( (string) ( $input['path'] ?? '' ) );
				if ( is_wp_error( $path ) ) {
					return $path;
				}

				$guard = PathHelper::assert_php_write_allowed( $path );
				if ( is_wp_error( $guard ) ) {
					return $guard;
				}

				$content  = (string) ( $input['content'] ?? '' );
				$encoding = (string) ( $input['encoding'] ?? 'utf8' );
				if ( 'base64' === $encoding ) {
					$decoded = base64_decode( $content, true );
					if ( false === $decoded ) {
						return new \WP_Error( 'layrshift_invalid_base64', __( 'Invalid base64 content.', 'layrshift' ) );
					}
					$content = $decoded;
				}

				$dir = dirname( $path );
				if ( ! is_dir( $dir ) && ! wp_mkdir_p( $dir ) ) {
					return new \WP_Error( 'layrshift_mkdir_failed', __( 'Could not create directory.', 'layrshift' ) );
				}

				$bytes = file_put_contents( $path, $content );
				if ( false === $bytes ) {
					return new \WP_Error( 'layrshift_write_failed', __( 'Failed to write file.', 'layrshift' ) );
				}

				$sandboxed = PathHelper::is_sandbox_path( $path );
				if ( $sandboxed && PathHelper::is_php_file( $path ) ) {
					Sandbox::register_file( basename( $path ), true );
				}

				return array(
					'success'       => true,
					'bytes_written' => $bytes,
					'sandboxed'     => $sandboxed,
				);
			}
		);
	}
}
