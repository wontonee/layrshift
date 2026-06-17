<?php

declare(strict_types=1);

namespace LayrShift\WpOptimize;

use LayrShift\Plugin;

final class Loader {

	public static function register_abilities(): void {
		if ( ! Plugin::meets_requirements() || ! Plugin::is_abilities_enabled() ) {
			return;
		}

		require_once __DIR__ . '/bootstrap.php';

		if ( ! is_wp_optimize_available() ) {
			return;
		}

		foreach ( array( 'get-status.php', 'get-settings.php', 'purge-cache.php' ) as $file ) {
			require_once __DIR__ . '/' . $file;
		}
	}

	/** @return list<string> */
	public static function ability_names(): array {
		require_once __DIR__ . '/bootstrap.php';

		if ( ! is_wp_optimize_available() ) {
			return array();
		}

		return array(
			'layrshift/wp-optimize-get-status',
			'layrshift/wp-optimize-get-settings',
			'layrshift/wp-optimize-purge-cache',
		);
	}
}
