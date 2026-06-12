<?php

declare(strict_types=1);

namespace LayrShift\Smush;

use LayrShift\Plugin;

final class Loader {

	public static function register_abilities(): void {
		if ( ! Plugin::meets_requirements() || ! Plugin::is_abilities_enabled() ) {
			return;
		}

		require_once __DIR__ . '/bootstrap.php';

		if ( ! is_smush_available() ) {
			return;
		}

		foreach ( array( 'get-stats.php', 'list-unsmushed.php', 'run-bulk-smush.php' ) as $file ) {
			require_once __DIR__ . '/' . $file;
		}
	}

	/** @return list<string> */
	public static function ability_names(): array {
		require_once __DIR__ . '/bootstrap.php';

		if ( ! is_smush_available() ) {
			return array();
		}

		return array(
			'layrshift/smush-get-stats',
			'layrshift/smush-list-unsmushed',
			'layrshift/smush-run-bulk-smush',
		);
	}
}
