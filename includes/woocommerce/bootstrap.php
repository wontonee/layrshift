<?php

declare(strict_types=1);

namespace LayrShift\WooCommerce;

use WC_Product;
use WP_Error;

if (!defined('ABSPATH')) {
    exit();
}

function is_woocommerce_available(): bool
{
    return class_exists('WooCommerce');
}

/** @return true|WP_Error */
function require_woocommerce(): true|WP_Error
{
    if (!is_woocommerce_available()) {
        return new WP_Error('woocommerce_not_active', __('WooCommerce is not active on this site.', 'layrshift'));
    }

    return true;
}

/**
 * @return array<string, mixed>
 */
function collect_status(): array
{
    $product_counts = wp_count_posts('product');
    $order_counts   = wp_count_posts('shop_order');

    $order_summary = array();
    if ($order_counts instanceof \stdClass) {
        foreach (get_object_vars($order_counts) as $status => $count) {
            if (is_numeric($count) && (int) $count > 0) {
                $order_summary[$status] = (int) $count;
            }
        }
    }

    return array(
        'version'  => defined('WC_VERSION') ? (string) WC_VERSION : '',
        'currency' => function_exists('get_woocommerce_currency') ? get_woocommerce_currency() : '',
        'store_url' => function_exists('wc_get_page_permalink') ? (string) wc_get_page_permalink('shop') : '',
        'counts'   => array(
            'products' => array(
                'publish' => isset($product_counts->publish) ? (int) $product_counts->publish : 0,
                'draft'   => isset($product_counts->draft) ? (int) $product_counts->draft : 0,
            ),
            'orders'   => $order_summary,
        ),
    );
}

/**
 * @return array<string, mixed>|WP_Error
 */
function summarize_product(WC_Product $product): array|WP_Error
{
    return array(
        'id'            => $product->get_id(),
        'name'          => $product->get_name(),
        'slug'          => $product->get_slug(),
        'status'        => $product->get_status(),
        'type'          => $product->get_type(),
        'sku'           => $product->get_sku(),
        'price'         => $product->get_price(),
        'regular_price' => $product->get_regular_price(),
        'sale_price'    => $product->get_sale_price(),
        'stock_status'  => $product->get_stock_status(),
        'stock_quantity' => $product->get_stock_quantity(),
        'permalink'     => $product->get_permalink(),
    );
}
