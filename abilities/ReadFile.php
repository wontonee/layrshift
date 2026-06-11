<?php
/**
 * read_file ability.
 *
 * @package LayrShift
 */

declare( strict_types=1 );

namespace LayrShift\Abilities;

use LayrShift\PathHelper;

final class ReadFile {

	use AbilityTrait;

	/**
	 * @param array<string, mixed> $input
	 * @return array<string, mixed>|\WP_Error
	 */
	public static function execute( array $input ) {
		return self::run_logged(
			'layrshift/read-file',
			$input,
			static function () use ( $input ) {
				$path = PathHelper::resolve( (string) ( $input['path'] ?? '' ) );
				if ( is_wp_error( $path ) ) {
					return $path;
				}

				if ( ! file_exists( $path ) || ! is_file( $path ) ) {
					return new \WP_Error( 'layrshift_not_found', __( 'File not found.', 'layrshift' ) );
				}

				if ( ! is_readable( $path ) ) {
					return new \WP_Error( 'layrshift_not_readable', __( 'File is not readable.', 'layrshift' ) );
				}

				$content  = file_get_contents( $path );
				if ( false === $content ) {
					return new \WP_Error( 'layrshift_read_failed', __( 'Failed to read file.', 'layrshift' ) );
				}

				$encoding = 'utf8';
				if ( ! mb_check_encoding( $content, 'UTF-8' ) ) {
					$content  = base64_encode( $content );
					$encoding = 'base64';
				}

				$lines = explode( "\n", $content );
				$start = isset( $input['start_line'] ) ? max( 1, (int) $input['start_line'] ) : null;
				$end   = isset( $input['end_line'] ) ? max( 1, (int) $input['end_line'] ) : null;

				if ( 'utf8' === $encoding && ( null !== $start || null !== $end ) ) {
					$raw_lines = explode( "\n", (string) file_get_contents( $path ) );
					$start     = $start ?? 1;
					$end       = $end ?? count( $raw_lines );
					$slice     = array_slice( $raw_lines, $start - 1, $end - $start + 1 );
					$content   = implode( "\n", $slice );
				}

				return array(
					'content'         => $content,
					'encoding'        => $encoding,
					'total_lines'     => count( $lines ),
					'file_size_bytes' => filesize( $path ),
				);
			}
		);
	}
}
