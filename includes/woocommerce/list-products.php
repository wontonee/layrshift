<?php

declare(strict_types=1);

namespace LayrShift\WooCommerce;

use WP_Error;

if (!defined('ABSPATH')) {
    exit();
}

wp_register_ability('layrshift/woocommerce-list-products', [
    'label' => __('List WooCommerce Products', 'layrshift'),
    'description' => __('List WooCommerce products with pagination and optional status filter.', 'layrshift'),
    'category' => 'layrshift-woocommerce',
    'input_schema' => [
        'type' => 'object',
        'properties' => [
            'per_page' => ['type' => 'integer', 'default' => 20, 'minimum' => 1, 'maximum' => 100],
            'page' => ['type' => 'integer', 'default' => 1, 'minimum' => 1],
            'status' => ['type' => 'string', 'default' => 'any'],
        ],
        'additionalProperties' => false,
    ],
    'execute_callback' => __NAMESPACE__ . '\\woocommerce_list_products',
    'permission_callback' => array(\LayrShift\Auth::class, 'check_ability_permission'),
    'meta' => [
        'mcp' => ['public' => true],
        'annotations' => ['readonly' => true, 'destructive' => false, 'idempotent' => true],
    ],
]);

/** @param array<string, mixed> $input */
function woocommerce_list_products(array $input): array|WP_Error
{
    $ready = require_woocommerce();
    if ($ready instanceof WP_Error) {
        return $ready;
    }

    $per_page = isset($input['per_page']) && is_numeric($input['per_page']) ? max(1, min(100, (int) $input['per_page'])) : 20;
    $page     = isset($input['page']) && is_numeric($input['page']) ? max(1, (int) $input['page']) : 1;
    $status   = isset($input['status']) && is_string($input['status']) ? $input['status'] : 'any';

    $query = new \WP_Query(
        array(
            'post_type'      => 'product',
            'post_status'    => $status,
            'posts_per_page' => $per_page,
            'paged'          => $page,
            'orderby'        => 'date',
            'order'          => 'DESC',
            'fields'         => 'ids',
        )
    );

    $items = array();
    foreach ($query->posts as $post_id) {
        $product = wc_get_product((int) $post_id);
        if (!$product) {
            continue;
        }
        $items[] = array(
            'id'     => $product->get_id(),
            'name'   => $product->get_name(),
            'status' => $product->get_status(),
            'type'   => $product->get_type(),
            'sku'    => $product->get_sku(),
            'price'  => $product->get_price(),
        );
    }

    return array(
        'page'        => $page,
        'per_page'    => $per_page,
        'total'       => (int) $query->found_posts,
        'total_pages' => (int) $query->max_num_pages,
        'products'    => $items,
    );
}
