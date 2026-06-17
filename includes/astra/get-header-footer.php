<?php

declare(strict_types=1);

namespace LayrShift\Astra;

use WP_Error;

if (!defined('ABSPATH')) {
    exit();
}

wp_register_ability('layrshift/astra-get-header-footer', [
    'label' => __('Get Astra Header Footer Builder', 'layrshift'),
    'description' => __('List Astra custom layout/hook posts used for header, footer, and hooks.', 'layrshift'),
    'category' => 'layrshift-astra',
    'input_schema' => [
        'type' => 'object',
        'properties' => (object) [],
        'additionalProperties' => false,
    ],
    'execute_callback' => __NAMESPACE__ . '\\astra_get_header_footer',
    'permission_callback' => array(\LayrShift\Auth::class, 'check_ability_permission'),
    'meta' => [
        'mcp' => ['public' => true],
        'annotations' => ['readonly' => true, 'destructive' => false, 'idempotent' => true],
    ],
]);

/** @param array<string, mixed> $input */
function astra_get_header_footer(array $input): array|WP_Error
{
    unset($input);

    $ready = require_astra();
    if ($ready instanceof WP_Error) {
        return $ready;
    }

    return collect_header_footer();
}
