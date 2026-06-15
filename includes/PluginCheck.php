<?php
/**
 * Align local Plugin Check scans with the release package (.distignore).
 *
 * @package LayrShift
 */

declare( strict_types=1 );

namespace LayrShift;

/**
 * Registers Plugin Check ignore paths so admin scans match the wp.org zip.
 */
final class PluginCheck {

	public static function init(): void {
		add_filter( 'wp_plugin_check_ignore_directories', array( self::class, 'ignore_directories' ) );
		add_filter( 'wp_plugin_check_ignore_files', array( self::class, 'ignore_files' ) );
	}

	/**
	 * @param array<int, string> $directories Directories Plugin Check skips.
	 * @return array<int, string>
	 */
	public static function ignore_directories( array $directories ): array {
		$extra = array(
			'tests',
			'scripts',
			'docs',
			'.cursor',
		);

		return array_values( array_unique( array_merge( $directories, $extra ) ) );
	}

	/**
	 * @param array<int, string> $files Files Plugin Check skips (paths relative to plugin root).
	 * @return array<int, string>
	 */
	public static function ignore_files( array $files ): array {
		$extra = array(
			'.gitignore',
			'.gitattributes',
			'.distignore',
			'README.md',
			'composer.lock',
			'phpunit.xml.dist',
			'.phpunit.result.cache',
			'abilities/ExecutePhp.php',
			'abilities/RunWpCli.php',
			'admin/views/tabs/generate.php',
			'admin/views/tabs/preview.php',
			'admin/assets/template-studio.js',
		);

		return array_values( array_unique( array_merge( $files, $extra ) ) );
	}
}
