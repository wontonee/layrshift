<?php

declare(strict_types=1);

namespace LayrShift\UpdraftPlus;

use LayrShift\Plugin;

final class Loader {

	public static function register_abilities(): void {
		if ( ! Plugin::meets_requirements() || ! Plugin::is_abilities_enabled() ) {
			return;
		}

		require_once __DIR__ . '/bootstrap.php';

		if ( ! is_updraftplus_available() ) {
			return;
		}

		foreach ( array( 'get-status.php', 'list-backups.php', 'get-settings.php' ) as $file ) {
			require_once __DIR__ . '/' . $file;
		}
	}

	/** @return list<string> */
	public static function ability_names(): array {
		require_once __DIR__ . '/bootstrap.php';

		if ( ! is_updraftplus_available() ) {
			return array();
		}

		return array(
			'layrshift/updraftplus-get-status',
			'layrshift/updraftplus-list-backups',
			'layrshift/updraftplus-get-settings',
		);
	}
}
