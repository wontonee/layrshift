<?php

declare(strict_types=1);

namespace LayrShift\UpdraftPlus;

use WP_Error;

if (!defined('ABSPATH')) {
    exit();
}

wp_register_ability('layrshift/updraftplus-get-status', [
    'label' => __('Get UpdraftPlus Status', 'layrshift'),
    'description' => __('Read UpdraftPlus version and last backup timestamp.', 'layrshift'),
    'category' => 'layrshift-updraftplus',
    'input_schema' => [
        'type' => 'object',
        'properties' => (object) [],
        'additionalProperties' => false,
    ],
    'execute_callback' => __NAMESPACE__ . '\\updraftplus_get_status',
    'permission_callback' => array(\LayrShift\Auth::class, 'check_ability_permission'),
    'meta' => [
        'mcp' => ['public' => true],
        'annotations' => ['readonly' => true, 'destructive' => false, 'idempotent' => true],
    ],
]);

/** @param array<string, mixed> $input */
function updraftplus_get_status(array $input): array|WP_Error
{
    unset($input);

    $ready = require_updraftplus();
    if ($ready instanceof WP_Error) {
        return $ready;
    }

    return collect_status();
}
