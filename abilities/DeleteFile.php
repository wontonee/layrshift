<?php
/**
 * delete_file ability.
 *
 * @package LayrShift
 */

declare( strict_types=1 );

namespace LayrShift\Abilities;

use LayrShift\PathHelper;
use LayrShift\Sandbox;

final class DeleteFile {

	use AbilityTrait;

	/**
	 * @param array<string, mixed> $input
	 * @return array<string, mixed>|\WP_Error
	 */
	public static function execute( array $input ) {
		return self::run_logged(
			'layrshift/delete-file',
			$input,
			static function () use ( $input ) {
				$path = PathHelper::resolve( (string) ( $input['path'] ?? '' ) );
				if ( is_wp_error( $path ) ) {
					return $path;
				}

				$guard = PathHelper::assert_core_deletion_allowed( $path );
				if ( is_wp_error( $guard ) ) {
					return $guard;
				}

				if ( ! file_exists( $path ) ) {
					return new \WP_Error( 'layrshift_not_found', __( 'Path not found.', 'layrshift' ) );
				}

				$recursive = ! empty( $input['recursive'] );
				$deleted   = 0;

				if ( is_dir( $path ) ) {
					if ( ! $recursive ) {
						$items = scandir( $path );
						if ( is_array( $items ) && count( $items ) > 2 ) {
							return new \WP_Error( 'layrshift_not_empty', __( 'Directory is not empty. Set recursive to true.', 'layrshift' ) );
						}
					}
					$deleted = self::delete_directory( $path );
				} else {
					if ( unlink( $path ) ) {
						$deleted = 1;
					}
				}

				if ( 0 === $deleted ) {
					return new \WP_Error( 'layrshift_delete_failed', __( 'Failed to delete path.', 'layrshift' ) );
				}

				if ( PathHelper::is_sandbox_path( $path ) ) {
					Sandbox::unregister_file( basename( $path ) );
				}

				return array(
					'success'       => true,
					'items_deleted' => $deleted,
				);
			}
		);
	}

	private static function delete_directory( string $dir ): int {
		$count = 0;
		$items = new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator( $dir, \RecursiveDirectoryIterator::SKIP_DOTS ),
			\RecursiveIteratorIterator::CHILD_FIRST
		);

		foreach ( $items as $item ) {
			if ( $item->isDir() ) {
				if ( rmdir( $item->getPathname() ) ) {
					++$count;
				}
			} elseif ( unlink( $item->getPathname() ) ) {
				++$count;
			}
		}

		if ( rmdir( $dir ) ) {
			++$count;
		}

		return $count;
	}
}
