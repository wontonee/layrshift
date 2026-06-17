<?php

declare(strict_types=1);

namespace LayrShift\WooCommerce;

use LayrShift\Plugin;

final class Loader {

	public static function register_abilities(): void {
		if ( ! Plugin::meets_requirements() || ! Plugin::is_abilities_enabled() ) {
			return;
		}

		require_once __DIR__ . '/bootstrap.php';

		if ( ! is_woocommerce_available() ) {
			return;
		}

		foreach ( array( 'get-status.php', 'get-product.php', 'list-products.php' ) as $file ) {
			require_once __DIR__ . '/' . $file;
		}
	}

	/** @return list<string> */
	public static function ability_names(): array {
		require_once __DIR__ . '/bootstrap.php';

		if ( ! is_woocommerce_available() ) {
			return array();
		}

		return array(
			'layrshift/woocommerce-get-status',
			'layrshift/woocommerce-get-product',
			'layrshift/woocommerce-list-products',
		);
	}
}
