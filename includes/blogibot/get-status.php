<?php

declare(strict_types=1);

namespace LayrShift\Blogibot;

use WP_Error;

if (!defined('ABSPATH')) {
    exit();
}

wp_register_ability('layrshift/blogibot-get-status', [
    'label' => __('Get BlogiBot Status', 'layrshift'),
    'description' => __('Probe BlogiBot availability, version, and detected content post types.', 'layrshift'),
    'category' => 'layrshift-blogibot',
    'input_schema' => [
        'type' => 'object',
        'properties' => (object) [],
        'additionalProperties' => false,
    ],
    'execute_callback' => __NAMESPACE__ . '\\blogibot_get_status',
    'permission_callback' => array(\LayrShift\Auth::class, 'check_ability_permission'),
    'meta' => [
        'mcp' => ['public' => true],
        'annotations' => ['readonly' => true, 'destructive' => false, 'idempotent' => true],
    ],
]);

/** @param array<string, mixed> $input */
function blogibot_get_status(array $input): array|WP_Error
{
    unset($input);

    $ready = require_blogibot();
    if ($ready instanceof WP_Error) {
        return $ready;
    }

    return array(
        'blogibot_version' => defined('BLOGIBOT_VERSION') ? (string) BLOGIBOT_VERSION : 'active',
        'post_types' => detect_post_types(),
        'settings_keys' => array_keys(read_settings()),
    );
}
