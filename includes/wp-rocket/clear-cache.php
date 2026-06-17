<?php

declare(strict_types=1);

namespace LayrShift\WpRocket;

use WP_Error;

if (!defined('ABSPATH')) {
    exit();
}

wp_register_ability('layrshift/wp-rocket-clear-cache', [
    'label' => __('Clear WP Rocket Cache', 'layrshift'),
    'description' => __('Purge WP Rocket page cache and related optimized assets.', 'layrshift'),
    'category' => 'layrshift-wp-rocket',
    'input_schema' => [
        'type' => 'object',
        'properties' => (object) [],
        'additionalProperties' => false,
    ],
    'execute_callback' => __NAMESPACE__ . '\\wp_rocket_clear_cache',
    'permission_callback' => array(\LayrShift\Auth::class, 'check_ability_permission'),
    'meta' => [
        'mcp' => ['public' => true],
        'annotations' => ['readonly' => false, 'destructive' => true, 'idempotent' => true],
    ],
]);

/** @param array<string, mixed> $input */
function wp_rocket_clear_cache(array $input): array|WP_Error
{
    unset($input);

    $ready = require_wp_rocket();
    if ($ready instanceof WP_Error) {
        return $ready;
    }

    return clear_cache();
}
