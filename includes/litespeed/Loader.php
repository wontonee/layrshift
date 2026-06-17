<?php

declare(strict_types=1);

namespace LayrShift\LiteSpeed;

use LayrShift\Plugin;

final class Loader {

	public static function register_abilities(): void {
		if ( ! Plugin::meets_requirements() || ! Plugin::is_abilities_enabled() ) {
			return;
		}

		require_once __DIR__ . '/bootstrap.php';

		if ( ! is_litespeed_available() ) {
			return;
		}

		foreach ( array( 'get-status.php', 'get-settings.php', 'purge-all.php' ) as $file ) {
			require_once __DIR__ . '/' . $file;
		}
	}

	/** @return list<string> */
	public static function ability_names(): array {
		require_once __DIR__ . '/bootstrap.php';

		if ( ! is_litespeed_available() ) {
			return array();
		}

		return array(
			'layrshift/litespeed-get-status',
			'layrshift/litespeed-get-settings',
			'layrshift/litespeed-purge-all',
		);
	}
}
