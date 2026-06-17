<?php

declare(strict_types=1);

namespace LayrShift\Astra;

use WP_Error;

if (!defined('ABSPATH')) {
    exit();
}

wp_register_ability('layrshift/astra-get-status', [
    'label' => __('Get Astra Status', 'layrshift'),
    'description' => __('Read Astra theme version and addon status.', 'layrshift'),
    'category' => 'layrshift-astra',
    'input_schema' => [
        'type' => 'object',
        'properties' => (object) [],
        'additionalProperties' => false,
    ],
    'execute_callback' => __NAMESPACE__ . '\\astra_get_status',
    'permission_callback' => array(\LayrShift\Auth::class, 'check_ability_permission'),
    'meta' => [
        'mcp' => ['public' => true],
        'annotations' => ['readonly' => true, 'destructive' => false, 'idempotent' => true],
    ],
]);

/** @param array<string, mixed> $input */
function astra_get_status(array $input): array|WP_Error
{
    unset($input);

    $ready = require_astra();
    if ($ready instanceof WP_Error) {
        return $ready;
    }

    return collect_status();
}
