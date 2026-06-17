<?php

declare(strict_types=1);

namespace LayrShift\Genesis;

use WP_Error;

if (!defined('ABSPATH')) {
    exit();
}

wp_register_ability('layrshift/genesis-get-status', [
    'label' => __('Get Genesis Status', 'layrshift'),
    'description' => __('Read Genesis Framework version, parent/child theme, and available layouts.', 'layrshift'),
    'category' => 'layrshift-genesis',
    'input_schema' => [
        'type' => 'object',
        'properties' => (object) [],
        'additionalProperties' => false,
    ],
    'execute_callback' => __NAMESPACE__ . '\\genesis_get_status',
    'permission_callback' => array(\LayrShift\Auth::class, 'check_ability_permission'),
    'meta' => [
        'mcp' => ['public' => true],
        'annotations' => ['readonly' => true, 'destructive' => false, 'idempotent' => true],
    ],
]);

/** @param array<string, mixed> $input */
function genesis_get_status(array $input): array|WP_Error
{
    unset($input);

    $ready = require_genesis();
    if ($ready instanceof WP_Error) {
        return $ready;
    }

    return collect_status();
}
