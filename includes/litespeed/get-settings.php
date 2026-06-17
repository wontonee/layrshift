<?php

declare(strict_types=1);

namespace LayrShift\LiteSpeed;

use WP_Error;

if (!defined('ABSPATH')) {
    exit();
}

wp_register_ability('layrshift/litespeed-get-settings', [
    'label' => __('Get LiteSpeed Cache Settings', 'layrshift'),
    'description' => __('Read safe LiteSpeed Cache configuration options.', 'layrshift'),
    'category' => 'layrshift-litespeed',
    'input_schema' => ['type' => 'object', 'properties' => (object) [], 'additionalProperties' => false],
    'execute_callback' => __NAMESPACE__ . '\\litespeed_get_settings',
    'permission_callback' => array(\LayrShift\Auth::class, 'check_ability_permission'),
    'meta' => ['mcp' => ['public' => true], 'annotations' => ['readonly' => true, 'destructive' => false, 'idempotent' => true]],
]);

/** @param array<string, mixed> $input */
function litespeed_get_settings(array $input): array|WP_Error
{
    unset($input);
    $ready = require_litespeed();
    if ($ready instanceof WP_Error) {
        return $ready;
    }
    return collect_settings();
}
