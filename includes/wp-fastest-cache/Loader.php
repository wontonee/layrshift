<?php

declare(strict_types=1);

namespace LayrShift\WpFastestCache;

use LayrShift\Plugin;

final class Loader {

	public static function register_abilities(): void {
		if ( ! Plugin::meets_requirements() || ! Plugin::is_abilities_enabled() ) {
			return;
		}

		require_once __DIR__ . '/bootstrap.php';

		if ( ! is_wp_fastest_cache_available() ) {
			return;
		}

		foreach ( array( 'get-status.php', 'get-settings.php', 'clear-cache.php' ) as $file ) {
			require_once __DIR__ . '/' . $file;
		}
	}

	/** @return list<string> */
	public static function ability_names(): array {
		require_once __DIR__ . '/bootstrap.php';

		if ( ! is_wp_fastest_cache_available() ) {
			return array();
		}

		return array(
			'layrshift/wp-fastest-cache-get-status',
			'layrshift/wp-fastest-cache-get-settings',
			'layrshift/wp-fastest-cache-clear-cache',
		);
	}
}
