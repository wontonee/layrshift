<?php

declare(strict_types=1);

namespace LayrShift\PrismShift;

use LayrShift\Plugin;

final class Loader {

	public static function register_abilities(): void {
		if ( ! Plugin::meets_requirements() || ! Plugin::is_abilities_enabled() ) {
			return;
		}

		require_once __DIR__ . '/bootstrap.php';

		if ( ! is_prismshift_available() ) {
			return;
		}

		foreach ( array(
			'get-post-seo.php',
			'update-post-seo.php',
			'get-site-settings.php',
			'analyze-post-seo.php',
			'ai-optimize-post.php',
		) as $file ) {
			require_once __DIR__ . '/' . $file;
		}
	}

	/** @return list<string> */
	public static function ability_names(): array {
		require_once __DIR__ . '/bootstrap.php';

		if ( ! is_prismshift_available() ) {
			return array();
		}

		return array(
			'layrshift/prismshift-get-post-seo',
			'layrshift/prismshift-update-post-seo',
			'layrshift/prismshift-get-site-settings',
			'layrshift/prismshift-analyze-post-seo',
			'layrshift/prismshift-ai-optimize-post',
		);
	}
}
