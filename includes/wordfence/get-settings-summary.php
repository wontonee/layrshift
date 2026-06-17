<?php

declare(strict_types=1);

namespace LayrShift\Wordfence;

use WP_Error;

if (!defined('ABSPATH')) {
    exit();
}

wp_register_ability('layrshift/wordfence-get-settings-summary', [
    'label' => __('Get Wordfence Settings Summary', 'layrshift'),
    'description' => __('Read high-level Wordfence settings without exposing API keys.', 'layrshift'),
    'category' => 'layrshift-wordfence',
    'input_schema' => [
        'type' => 'object',
        'properties' => (object) [],
        'additionalProperties' => false,
    ],
    'execute_callback' => __NAMESPACE__ . '\\wordfence_get_settings_summary',
    'permission_callback' => array(\LayrShift\Auth::class, 'check_ability_permission'),
    'meta' => [
        'mcp' => ['public' => true],
        'annotations' => ['readonly' => true, 'destructive' => false, 'idempotent' => true],
    ],
]);

/** @param array<string, mixed> $input */
function wordfence_get_settings_summary(array $input): array|WP_Error
{
    unset($input);

    $ready = require_wordfence();
    if ($ready instanceof WP_Error) {
        return $ready;
    }

    return collect_settings_summary();
}
