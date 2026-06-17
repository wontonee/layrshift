<?php

declare(strict_types=1);

namespace LayrShift\WpOptimize;

use WP_Error;

if (!defined('ABSPATH')) {
    exit();
}

wp_register_ability('layrshift/wp-optimize-purge-cache', [
    'label' => __('Purge WP-Optimize Cache', 'layrshift'),
    'description' => __('Purge WP-Optimize page cache and minified assets.', 'layrshift'),
    'category' => 'layrshift-wp-optimize',
    'input_schema' => ['type' => 'object', 'properties' => (object) [], 'additionalProperties' => false],
    'execute_callback' => __NAMESPACE__ . '\\wp_optimize_purge_cache',
    'permission_callback' => array(\LayrShift\Auth::class, 'check_ability_permission'),
    'meta' => ['mcp' => ['public' => true], 'annotations' => ['readonly' => false, 'destructive' => true, 'idempotent' => true]],
]);

/** @param array<string, mixed> $input */
function wp_optimize_purge_cache(array $input): array|WP_Error
{
    unset($input);
    $ready = require_wp_optimize();
    if ($ready instanceof WP_Error) {
        return $ready;
    }
    return purge_cache();
}
