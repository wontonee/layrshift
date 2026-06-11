<?php
/**
 * PHP sandbox manager.
 *
 * @package LayrShift
 */

declare( strict_types=1 );

namespace LayrShift;

/**
 * Sandbox directory, manifest, and autoload.
 */
final class Sandbox {

	private const MANIFEST_FILE = 'manifest.json';

	public static function init(): void {
		add_action( 'plugins_loaded', array( self::class, 'autoload_files' ), 1 );
	}

	public static function get_directory(): string {
		return wp_normalize_path( WP_CONTENT_DIR . '/layrshift-sandbox' );
	}

	public static function get_manifest_path(): string {
		return self::get_directory() . '/' . self::MANIFEST_FILE;
	}

	public static function ensure_directory(): void {
		$dir = self::get_directory();
		if ( ! is_dir( $dir ) ) {
			wp_mkdir_p( $dir );
		}

		$manifest_path = self::get_manifest_path();
		if ( ! file_exists( $manifest_path ) ) {
			file_put_contents( $manifest_path, wp_json_encode( array(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) );
		}

		$htaccess = $dir . '/.htaccess';
		if ( ! file_exists( $htaccess ) ) {
			file_put_contents( $htaccess, "Deny from all\n" );
		}
	}

	public static function is_safe_mode_active(): bool {
		return file_exists( self::get_directory() . '/safe-mode.flag' );
	}

	public static function enable_safe_mode(): void {
		self::ensure_directory();
		file_put_contents( self::get_directory() . '/safe-mode.flag', gmdate( 'c' ) );
	}

	public static function disable_safe_mode(): void {
		$flag = self::get_directory() . '/safe-mode.flag';
		if ( file_exists( $flag ) ) {
			unlink( $flag );
		}
	}

	public static function autoload_files(): void {
		if ( self::is_safe_mode_active() ) {
			return;
		}

		$manifest = self::get_manifest();
		foreach ( $manifest as $entry ) {
			if ( empty( $entry['filename'] ) || empty( $entry['active'] ) ) {
				continue;
			}

			$file = self::get_directory() . '/' . basename( (string) $entry['filename'] );
			if ( is_readable( $file ) && self::is_php_file( $file ) ) {
				require_once $file;
			}
		}
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_manifest(): array {
		self::ensure_directory();
		$path = self::get_manifest_path();
		if ( ! file_exists( $path ) ) {
			return array();
		}

		$data = json_decode( (string) file_get_contents( $path ), true );
		return is_array( $data ) ? $data : array();
	}

	/**
	 * @param array<int, array<string, mixed>> $manifest Manifest entries.
	 */
	public static function save_manifest( array $manifest ): void {
		file_put_contents(
			self::get_manifest_path(),
			wp_json_encode( array_values( $manifest ), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES )
		);
	}

	public static function register_file( string $filename, bool $active = true ): void {
		$filename = basename( $filename );
		$manifest = self::get_manifest();
		$found    = false;

		foreach ( $manifest as &$entry ) {
			if ( ( $entry['filename'] ?? '' ) === $filename ) {
				$entry['active']        = $active;
				$entry['last_modified'] = gmdate( 'c' );
				$found                  = true;
				break;
			}
		}
		unset( $entry );

		if ( ! $found ) {
			$manifest[] = array(
				'filename'       => $filename,
				'active'         => $active,
				'last_modified'  => gmdate( 'c' ),
			);
		}

		self::save_manifest( $manifest );
	}

	public static function unregister_file( string $filename ): void {
		$filename = basename( $filename );
		$manifest = array_values(
			array_filter(
				self::get_manifest(),
				static fn( array $entry ): bool => ( $entry['filename'] ?? '' ) !== $filename
			)
		);
		self::save_manifest( $manifest );
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	public static function list_files(): array {
		self::ensure_directory();
		$dir      = self::get_directory();
		$manifest = self::get_manifest();
		$indexed  = array();

		foreach ( $manifest as $entry ) {
			if ( ! empty( $entry['filename'] ) ) {
				$indexed[ $entry['filename'] ] = $entry;
			}
		}

		$files = glob( $dir . '/*.php*' ) ?: array();
		$result = array();

		foreach ( $files as $file ) {
			$name = basename( $file );
			if ( str_ends_with( $name, '.bak' ) ) {
				continue;
			}

			$disabled = str_ends_with( $name, '.php.disabled' );
			$base     = $disabled ? str_replace( '.php.disabled', '.php', $name ) : $name;

			if ( ! self::is_php_file( $base ) && ! $disabled ) {
				continue;
			}

			$entry = $indexed[ $base ] ?? array(
				'filename'      => $base,
				'active'        => ! $disabled,
				'last_modified' => gmdate( 'c', (int) filemtime( $file ) ),
			);

			$result[] = array(
				'filename'      => $base,
				'status'        => $disabled ? 'disabled' : ( ! empty( $entry['active'] ) ? 'active' : 'disabled' ),
				'size'          => filesize( $file ),
				'last_modified' => gmdate( 'c', (int) filemtime( $file ) ),
				'path'          => $file,
			);
		}

		return $result;
	}

	public static function disable_file( string $filename ): bool|\WP_Error {
		$filename = basename( $filename );
		if ( ! self::is_php_file( $filename ) ) {
			return new \WP_Error( 'layrshift_invalid_file', __( 'Only sandbox PHP files can be disabled.', 'layrshift' ) );
		}

		$source = self::get_directory() . '/' . $filename;
		$target = $source . '.disabled';

		if ( ! file_exists( $source ) ) {
			return new \WP_Error( 'layrshift_not_found', __( 'Sandbox file not found.', 'layrshift' ) );
		}

		if ( ! rename( $source, $target ) ) {
			return new \WP_Error( 'layrshift_disable_failed', __( 'Could not disable sandbox file.', 'layrshift' ) );
		}

		self::register_file( $filename, false );
		return true;
	}

	public static function enable_file( string $filename ): bool|\WP_Error {
		$filename = basename( str_replace( '.disabled', '', $filename ) );
		$source   = self::get_directory() . '/' . $filename . '.disabled';
		$target   = self::get_directory() . '/' . $filename;

		if ( ! file_exists( $source ) ) {
			return new \WP_Error( 'layrshift_not_found', __( 'Disabled sandbox file not found.', 'layrshift' ) );
		}

		if ( ! rename( $source, $target ) ) {
			return new \WP_Error( 'layrshift_enable_failed', __( 'Could not enable sandbox file.', 'layrshift' ) );
		}

		self::register_file( $filename, true );
		return true;
	}

	private static function is_php_file( string $filename ): bool {
		return (bool) preg_match( '/\.php$/i', $filename );
	}
}
