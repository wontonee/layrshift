<?php

declare(strict_types=1);

namespace LayrShift\VaultShift;

use LayrShift\Plugin;

final class Loader {

	public static function register_abilities(): void {
		if ( ! Plugin::meets_requirements() || ! Plugin::is_abilities_enabled() ) {
			return;
		}

		require_once __DIR__ . '/bootstrap.php';

		if ( ! is_vaultshift_available() ) {
			return;
		}

		foreach ( array( 'get-status.php', 'trigger-scan.php', 'list-activity.php' ) as $file ) {
			require_once __DIR__ . '/' . $file;
		}
	}

	/** @return list<string> */
	public static function ability_names(): array {
		require_once __DIR__ . '/bootstrap.php';

		if ( ! is_vaultshift_available() ) {
			return array();
		}

		return array(
			'layrshift/vaultshift-get-status',
			'layrshift/vaultshift-trigger-scan',
			'layrshift/vaultshift-list-activity',
		);
	}
}
