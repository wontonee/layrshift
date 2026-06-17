<?php

declare(strict_types=1);

namespace LayrShift\WpFastestCache;

use WP_Error;

if (!defined('ABSPATH')) {
    exit();
}

wp_register_ability('layrshift/wp-fastest-cache-get-settings', [
    'label' => __('Get WP Fastest Cache Settings', 'layrshift'),
    'description' => __('Read safe WP Fastest Cache configuration options.', 'layrshift'),
    'category' => 'layrshift-wp-fastest-cache',
    'input_schema' => ['type' => 'object', 'properties' => (object) [], 'additionalProperties' => false],
    'execute_callback' => __NAMESPACE__ . '\\wp_fastest_cache_get_settings',
    'permission_callback' => array(\LayrShift\Auth::class, 'check_ability_permission'),
    'meta' => ['mcp' => ['public' => true], 'annotations' => ['readonly' => true, 'destructive' => false, 'idempotent' => true]],
]);

/** @param array<string, mixed> $input */
function wp_fastest_cache_get_settings(array $input): array|WP_Error
{
    unset($input);
    $ready = require_wp_fastest_cache();
    if ($ready instanceof WP_Error) {
        return $ready;
    }
    return collect_settings();
}
