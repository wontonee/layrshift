<?php
/**
 * edit_file ability.
 *
 * @package LayrShift
 */

declare( strict_types=1 );

namespace LayrShift\Abilities;

use LayrShift\PathHelper;
use LayrShift\Sandbox;

final class EditFile {

	use AbilityTrait;

	/**
	 * @param array<string, mixed> $input
	 * @return array<string, mixed>|\WP_Error
	 */
	public static function execute( array $input ) {
		return self::run_logged(
			'layrshift/edit-file',
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

				if ( ! file_exists( $path ) || ! is_file( $path ) ) {
					return new \WP_Error( 'layrshift_not_found', __( 'File not found.', 'layrshift' ) );
				}

				$old = (string) ( $input['old_string'] ?? '' );
				$new = (string) ( $input['new_string'] ?? '' );
				if ( '' === $old ) {
					return new \WP_Error( 'layrshift_empty_old', __( 'old_string cannot be empty.', 'layrshift' ) );
				}

				$content = file_get_contents( $path );
				if ( false === $content ) {
					return new \WP_Error( 'layrshift_read_failed', __( 'Failed to read file.', 'layrshift' ) );
				}

				$count = substr_count( $content, $old );
				if ( 0 === $count ) {
					return new \WP_Error( 'layrshift_not_found_string', __( 'old_string was not found in the file.', 'layrshift' ) );
				}
				if ( $count > 1 ) {
					return new \WP_Error( 'layrshift_ambiguous', __( 'old_string appears more than once; edit is ambiguous.', 'layrshift' ) );
				}

				$backup = $path . '.bak';
				if ( false === copy( $path, $backup ) ) {
					return new \WP_Error( 'layrshift_backup_failed', __( 'Could not create backup file.', 'layrshift' ) );
				}

				$updated = str_replace( $old, $new, $content );
				if ( false === file_put_contents( $path, $updated ) ) {
					return new \WP_Error( 'layrshift_write_failed', __( 'Failed to write file.', 'layrshift' ) );
				}

				if ( PathHelper::is_sandbox_path( $path ) && PathHelper::is_php_file( $path ) ) {
					Sandbox::register_file( basename( $path ), true );
				}

				return array(
					'success'     => true,
					'backup_path' => $backup,
				);
			}
		);
	}
}
