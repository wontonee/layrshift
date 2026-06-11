<?php
/**
 * Gutenberg queue subsystem loader.
 *
 * @package LayrShift
 */

declare( strict_types=1 );

namespace LayrShift\Gutenberg;

use LayrShift\Plugin;

/**
 * Loads runtime, REST, admin finalizer, and MCP abilities for the block editor queue.
 */
final class Loader {

	public static function init_runtime(): void {
		if ( ! Plugin::is_abilities_enabled() ) {
			return;
		}

		require_once __DIR__ . '/bootstrap.php';
		require_once __DIR__ . '/runtime.php';
		require_once __DIR__ . '/rest.php';

		if ( is_admin() ) {
			require_once dirname( __DIR__, 2 ) . '/admin/GutenbergFinalizer.php';
			\LayrShift\Admin\GutenbergFinalizer\boot_gutenberg_finalizer_admin();
		}
	}

	public static function register_abilities(): void {
		if ( ! Plugin::meets_requirements() || ! Plugin::is_abilities_enabled() ) {
			return;
		}

		require_once __DIR__ . '/bootstrap.php';
		require_once __DIR__ . '/runtime.php';

		$files = array(
			'get-finalizer-runtime.php',
			'get-content.php',
			'write-content.php',
			'create-pending-batch.php',
			'add-pending-change.php',
			'enable-batch-finalization.php',
			'get-pending-batch.php',
			'list-pending-batches.php',
			'delete-pending-batch.php',
			'delete-pending-change.php',
			'get-finalization-url.php',
		);

		foreach ( $files as $file ) {
			require_once __DIR__ . '/' . $file;
		}
	}

	public static function unschedule_cleanup(): void {
		require_once __DIR__ . '/bootstrap.php';
		\LayrShift\Gutenberg\unschedule_cleanup();
	}

	/**
	 * @return list<string>
	 */
	public static function ability_names(): array {
		return array(
			'layrshift/gutenberg-get-finalizer-runtime',
			'layrshift/gutenberg-get-content',
			'layrshift/gutenberg-write-content',
			'layrshift/gutenberg-create-pending-batch',
			'layrshift/gutenberg-add-pending-change',
			'layrshift/gutenberg-enable-batch-finalization',
			'layrshift/gutenberg-get-pending-batch',
			'layrshift/gutenberg-list-pending-batches',
			'layrshift/gutenberg-delete-pending-batch',
			'layrshift/gutenberg-delete-pending-change',
			'layrshift/gutenberg-get-finalization-url',
		);
	}
}
