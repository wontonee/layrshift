<?php

declare(strict_types=1);

namespace LayrShift\WooCommerce;

use WP_Error;

if (!defined('ABSPATH')) {
    exit();
}

wp_register_ability('layrshift/woocommerce-get-product', [
    'label' => __('Get WooCommerce Product', 'layrshift'),
    'description' => __('Read a single WooCommerce product summary by ID.', 'layrshift'),
    'category' => 'layrshift-woocommerce',
    'input_schema' => [
        'type' => 'object',
        'properties' => [
            'product_id' => ['type' => 'integer'],
        ],
        'required' => ['product_id'],
        'additionalProperties' => false,
    ],
    'execute_callback' => __NAMESPACE__ . '\\woocommerce_get_product',
    'permission_callback' => array(\LayrShift\Auth::class, 'check_ability_permission'),
    'meta' => [
        'mcp' => ['public' => true],
        'annotations' => ['readonly' => true, 'destructive' => false, 'idempotent' => true],
    ],
]);

/** @param array<string, mixed> $input */
function woocommerce_get_product(array $input): array|WP_Error
{
    $ready = require_woocommerce();
    if ($ready instanceof WP_Error) {
        return $ready;
    }

    $product_id = isset($input['product_id']) && is_scalar($input['product_id']) ? (int) $input['product_id'] : 0;
    if ($product_id <= 0) {
        return new WP_Error('woocommerce_invalid_product_id', __('A valid product_id is required.', 'layrshift'));
    }

    $product = wc_get_product($product_id);
    if (!$product) {
        return new WP_Error(
            'woocommerce_product_not_found',
            sprintf(
                /* translators: %d: product ID */
                __('Product %d was not found.', 'layrshift'),
                $product_id
            )
        );
    }

    return summarize_product($product);
}
