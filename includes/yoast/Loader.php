<?php

declare(strict_types=1);

namespace LayrShift\Yoast;

use LayrShift\Plugin;

final class Loader {

	public static function register_abilities(): void {
		if ( ! Plugin::meets_requirements() || ! Plugin::is_abilities_enabled() ) {
			return;
		}

		require_once __DIR__ . '/bootstrap.php';

		if ( ! is_yoast_available() ) {
			return;
		}

		foreach ( array( 'get-post-seo.php', 'update-post-seo.php', 'get-site-settings.php' ) as $file ) {
			require_once __DIR__ . '/' . $file;
		}
	}

	/** @return list<string> */
	public static function ability_names(): array {
		require_once __DIR__ . '/bootstrap.php';

		if ( ! is_yoast_available() ) {
			return array();
		}

		return array(
			'layrshift/yoast-get-post-seo',
			'layrshift/yoast-update-post-seo',
			'layrshift/yoast-get-site-settings',
		);
	}
}
