<?php
/**
 * Filesystem path helpers.
 *
 * @package LayrShift
 */

declare( strict_types=1 );

namespace LayrShift;

/**
 * Path normalization and guards.
 */
final class PathHelper {

	/**
	 * @return string|\WP_Error
	 */
	public static function resolve( string $path ) {
		$path = trim( $path );
		if ( '' === $path ) {
			return new \WP_Error( 'layrshift_empty_path', __( 'Path cannot be empty.', 'layrshift' ) );
		}

		if ( ! self::is_absolute( $path ) ) {
			$path = trailingslashit( ABSPATH ) . ltrim( $path, '/\\' );
		}

		$real = self::realpath_or_normalize( $path );
		if ( is_wp_error( $real ) ) {
			return $real;
		}

		if ( self::is_blocked_system_path( $real ) ) {
			return new \WP_Error( 'layrshift_blocked_path', __( 'Access to this path is not allowed.', 'layrshift' ) );
		}

		return $real;
	}

	public static function is_php_file( string $path ): bool {
		return 'php' === strtolower( pathinfo( $path, PATHINFO_EXTENSION ) );
	}

	public static function is_sandbox_path( string $path ): bool {
		$sandbox = Sandbox::get_directory();
		$real    = self::realpath_or_normalize( $path );
		if ( is_wp_error( $real ) ) {
			$real = wp_normalize_path( $path );
		}

		return 0 === strpos( wp_normalize_path( $real ), wp_normalize_path( $sandbox ) );
	}

	public static function assert_php_write_allowed( string $path ) {
		if ( ! self::is_php_file( $path ) ) {
			return true;
		}

		if ( ! self::is_sandbox_path( $path ) ) {
			return new \WP_Error(
				'layrshift_php_sandbox_only',
				__( 'PHP files may only be written inside the LayrShift sandbox directory.', 'layrshift' )
			);
		}

		return true;
	}

	public static function assert_core_deletion_allowed( string $path ) {
		$settings = Plugin::get_settings();
		if ( empty( $settings['restrict_core_deletion'] ) ) {
			return true;
		}

		$real = self::realpath_or_normalize( $path );
		if ( is_wp_error( $real ) ) {
			$real = wp_normalize_path( $path );
		}

		$real = wp_normalize_path( $real );
		$core = array(
			wp_normalize_path( ABSPATH . 'wp-includes' ),
			wp_normalize_path( ABSPATH . 'wp-admin' ),
		);

		foreach ( $core as $core_path ) {
			if ( 0 === strpos( $real, $core_path ) ) {
				return new \WP_Error(
					'layrshift_core_protected',
					__( 'Deletion of WordPress core files is restricted.', 'layrshift' )
				);
			}
		}

		return true;
	}

	/**
	 * @return string|\WP_Error
	 */
	private static function realpath_or_normalize( string $path ) {
		$normalized = wp_normalize_path( $path );

		if ( file_exists( $path ) ) {
			$real = realpath( $path );
			if ( false !== $real ) {
				return wp_normalize_path( $real );
			}
		}

		return $normalized;
	}

	private static function is_absolute( string $path ): bool {
		if ( isset( $path[0] ) && ( '/' === $path[0] || '\\' === $path[0] ) ) {
			return true;
		}

		return (bool) preg_match( '/^[A-Za-z]:[\\\\\\/]/', $path );
	}

	private static function is_blocked_system_path( string $path ): bool {
		$path = strtolower( wp_normalize_path( $path ) );
		$blocked = array( '/proc', '/sys' );

		foreach ( $blocked as $prefix ) {
			if ( 0 === strpos( $path, $prefix ) ) {
				return true;
			}
		}

		return false;
	}
}
