<?php

declare(strict_types=1);

namespace LayrShift\WooCommerce;

use WP_Error;

if (!defined('ABSPATH')) {
    exit();
}

wp_register_ability('layrshift/woocommerce-get-status', [
    'label' => __('Get WooCommerce Status', 'layrshift'),
    'description' => __('Read WooCommerce version, currency, store URL, and product/order counts.', 'layrshift'),
    'category' => 'layrshift-woocommerce',
    'input_schema' => [
        'type' => 'object',
        'properties' => (object) [],
        'additionalProperties' => false,
    ],
    'execute_callback' => __NAMESPACE__ . '\\woocommerce_get_status',
    'permission_callback' => array(\LayrShift\Auth::class, 'check_ability_permission'),
    'meta' => [
        'mcp' => ['public' => true],
        'annotations' => ['readonly' => true, 'destructive' => false, 'idempotent' => true],
    ],
]);

/** @param array<string, mixed> $input */
function woocommerce_get_status(array $input): array|WP_Error
{
    unset($input);

    $ready = require_woocommerce();
    if ($ready instanceof WP_Error) {
        return $ready;
    }

    return collect_status();
}
