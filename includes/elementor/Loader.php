<?php
/**
 * Elementor MCP abilities loader.
 *
 * @package LayrShift
 */

declare(strict_types=1);

namespace LayrShift\Elementor;

use LayrShift\Plugin;
use LayrShift\Pro\EditorDetector;

/**
 * Registers Elementor document abilities when Elementor is active.
 */
final class Loader {

	public static function register_abilities(): void {
		if ( ! Plugin::meets_requirements() || ! Plugin::is_abilities_enabled() ) {
			return;
		}

		if ( ! EditorDetector::is_elementor_available() ) {
			return;
		}

		require_once __DIR__ . '/bootstrap.php';

		$files = array(
			'get-document.php',
			'save-document.php',
			'list-templates.php',
		);

		foreach ( $files as $file ) {
			require_once __DIR__ . '/' . $file;
		}
	}

	/**
	 * @return list<string>
	 */
	public static function ability_names(): array {
		if ( ! EditorDetector::is_elementor_available() ) {
			return array();
		}

		return array(
			'layrshift/elementor-get-document',
			'layrshift/elementor-save-document',
			'layrshift/elementor-list-templates',
		);
	}
}
