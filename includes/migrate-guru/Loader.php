<?php

declare(strict_types=1);

namespace LayrShift\MigrateGuru;

use LayrShift\Plugin;

final class Loader {

	public static function register_abilities(): void {
		if ( ! Plugin::meets_requirements() || ! Plugin::is_abilities_enabled() ) {
			return;
		}

		require_once __DIR__ . '/bootstrap.php';

		if ( ! is_migrate_guru_available() ) {
			return;
		}

		foreach ( array( 'get-status.php', 'get-connection-info.php', 'get-migration-state.php' ) as $file ) {
			require_once __DIR__ . '/' . $file;
		}
	}

	/** @return list<string> */
	public static function ability_names(): array {
		require_once __DIR__ . '/bootstrap.php';

		if ( ! is_migrate_guru_available() ) {
			return array();
		}

		return array(
			'layrshift/migrate-guru-get-status',
			'layrshift/migrate-guru-get-connection-info',
			'layrshift/migrate-guru-get-migration-state',
		);
	}
}
