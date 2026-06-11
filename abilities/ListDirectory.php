<?php
/**
 * list_directory ability.
 *
 * @package LayrShift
 */

declare( strict_types=1 );

namespace LayrShift\Abilities;

use LayrShift\PathHelper;

final class ListDirectory {

	use AbilityTrait;

	/**
	 * @param array<string, mixed> $input
	 * @return array<string, mixed>|\WP_Error
	 */
	public static function execute( array $input ) {
		return self::run_logged(
			'layrshift/list-directory',
			$input,
			static function () use ( $input ) {
				$path = PathHelper::resolve( (string) ( $input['path'] ?? '' ) );
				if ( is_wp_error( $path ) ) {
					return $path;
				}

				if ( ! is_dir( $path ) ) {
					return new \WP_Error( 'layrshift_not_directory', __( 'Path is not a directory.', 'layrshift' ) );
				}

				$pattern   = isset( $input['pattern'] ) ? (string) $input['pattern'] : null;
				$recursive = ! empty( $input['recursive'] );
				$max_depth = max( 1, min( 10, (int) ( $input['max_depth'] ?? 3 ) ) );

				$entries = self::scan( $path, $pattern, $recursive, $max_depth, 0 );

				return array(
					'entries' => $entries,
					'total'   => self::count_entries( $entries ),
				);
			}
		);
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	private static function scan( string $path, ?string $pattern, bool $recursive, int $max_depth, int $depth ): array {
		$entries = array();
		$items   = scandir( $path );
		if ( ! is_array( $items ) ) {
			return $entries;
		}

		foreach ( $items as $item ) {
			if ( '.' === $item || '..' === $item ) {
				continue;
			}

			if ( null !== $pattern && ! fnmatch( $pattern, $item ) ) {
				continue;
			}

			$full = wp_normalize_path( $path . '/' . $item );
			$is_dir = is_dir( $full ) && ! is_link( $full );

			$entry = array(
				'name'     => $item,
				'type'     => $is_dir ? 'directory' : 'file',
				'size'     => $is_dir ? 0 : (int) @filesize( $full ),
				'modified' => gmdate( 'c', (int) @filemtime( $full ) ),
			);

			if ( $is_dir && $recursive && $depth < $max_depth ) {
				$entry['children'] = self::scan( $full, $pattern, true, $max_depth, $depth + 1 );
			}

			$entries[] = $entry;
		}

		return $entries;
	}

	/**
	 * @param array<int, array<string, mixed>> $entries
	 */
	private static function count_entries( array $entries ): int {
		$count = count( $entries );
		foreach ( $entries as $entry ) {
			if ( ! empty( $entry['children'] ) && is_array( $entry['children'] ) ) {
				$count += self::count_entries( $entry['children'] );
			}
		}
		return $count;
	}
}
