<?php

declare(strict_types=1);

namespace LayrShift\WpOptimize;

use WP_Error;

if (!defined('ABSPATH')) {
    exit();
}

wp_register_ability('layrshift/wp-optimize-get-status', [
    'label' => __('Get WP-Optimize Status', 'layrshift'),
    'description' => __('Read WP-Optimize version and cache feature flags.', 'layrshift'),
    'category' => 'layrshift-wp-optimize',
    'input_schema' => ['type' => 'object', 'properties' => (object) [], 'additionalProperties' => false],
    'execute_callback' => __NAMESPACE__ . '\\wp_optimize_get_status',
    'permission_callback' => array(\LayrShift\Auth::class, 'check_ability_permission'),
    'meta' => ['mcp' => ['public' => true], 'annotations' => ['readonly' => true, 'destructive' => false, 'idempotent' => true]],
]);

/** @param array<string, mixed> $input */
function wp_optimize_get_status(array $input): array|WP_Error
{
    unset($input);
    $ready = require_wp_optimize();
    if ($ready instanceof WP_Error) {
        return $ready;
    }
    return collect_status();
}
