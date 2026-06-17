<?php

declare(strict_types=1);

namespace LayrShift\WpRocket;

use WP_Error;

if (!defined('ABSPATH')) {
    exit();
}

wp_register_ability('layrshift/wp-rocket-get-status', [
    'label' => __('Get WP Rocket Status', 'layrshift'),
    'description' => __('Read WP Rocket version and cache feature flags.', 'layrshift'),
    'category' => 'layrshift-wp-rocket',
    'input_schema' => [
        'type' => 'object',
        'properties' => (object) [],
        'additionalProperties' => false,
    ],
    'execute_callback' => __NAMESPACE__ . '\\wp_rocket_get_status',
    'permission_callback' => array(\LayrShift\Auth::class, 'check_ability_permission'),
    'meta' => [
        'mcp' => ['public' => true],
        'annotations' => ['readonly' => true, 'destructive' => false, 'idempotent' => true],
    ],
]);

/** @param array<string, mixed> $input */
function wp_rocket_get_status(array $input): array|WP_Error
{
    unset($input);

    $ready = require_wp_rocket();
    if ($ready instanceof WP_Error) {
        return $ready;
    }

    return collect_status();
}
