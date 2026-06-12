<?php

declare(strict_types=1);

namespace LayrShift\Blogibot;

use WP_Error;

if (!defined('ABSPATH')) {
    exit();
}

wp_register_ability('layrshift/blogibot-get-settings', [
    'label' => __('Get BlogiBot Settings', 'layrshift'),
    'description' => __('Read BlogiBot plugin options when exposed via WordPress options API.', 'layrshift'),
    'category' => 'layrshift-blogibot',
    'input_schema' => [
        'type' => 'object',
        'properties' => (object) [],
        'additionalProperties' => false,
    ],
    'execute_callback' => __NAMESPACE__ . '\\blogibot_get_settings',
    'permission_callback' => array(\LayrShift\Auth::class, 'check_ability_permission'),
    'meta' => [
        'mcp' => ['public' => true],
        'annotations' => ['readonly' => true, 'destructive' => false, 'idempotent' => true],
    ],
]);

/** @param array<string, mixed> $input */
function blogibot_get_settings(array $input): array|WP_Error
{
    unset($input);

    $ready = require_blogibot();
    if ($ready instanceof WP_Error) {
        return $ready;
    }

    $settings = read_settings();

    return array(
        'settings' => $settings,
        'has_settings' => $settings !== array(),
    );
}
