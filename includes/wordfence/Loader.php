<?php

declare(strict_types=1);

namespace LayrShift\Wordfence;

use LayrShift\Plugin;

final class Loader {

	public static function register_abilities(): void {
		if ( ! Plugin::meets_requirements() || ! Plugin::is_abilities_enabled() ) {
			return;
		}

		require_once __DIR__ . '/bootstrap.php';

		if ( ! is_wordfence_available() ) {
			return;
		}

		foreach ( array( 'get-status.php', 'get-scan-summary.php', 'get-settings-summary.php' ) as $file ) {
			require_once __DIR__ . '/' . $file;
		}
	}

	/** @return list<string> */
	public static function ability_names(): array {
		require_once __DIR__ . '/bootstrap.php';

		if ( ! is_wordfence_available() ) {
			return array();
		}

		return array(
			'layrshift/wordfence-get-status',
			'layrshift/wordfence-get-scan-summary',
			'layrshift/wordfence-get-settings-summary',
		);
	}
}
