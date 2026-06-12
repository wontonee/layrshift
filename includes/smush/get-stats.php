<?php

declare(strict_types=1);

namespace LayrShift\Smush;

use WP_Error;

if (!defined('ABSPATH')) {
    exit();
}

wp_register_ability('layrshift/smush-get-stats', [
    'label' => __('Get Smush Stats', 'layrshift'),
    'description' => __('Read Smush optimization statistics and key settings.', 'layrshift'),
    'category' => 'layrshift-smush',
    'input_schema' => [
        'type' => 'object',
        'properties' => (object) [],
        'additionalProperties' => false,
    ],
    'execute_callback' => __NAMESPACE__ . '\\smush_get_stats',
    'permission_callback' => array(\LayrShift\Auth::class, 'check_ability_permission'),
    'meta' => [
        'mcp' => ['public' => true],
        'annotations' => ['readonly' => true, 'destructive' => false, 'idempotent' => true],
    ],
]);

/** @param array<string, mixed> $input */
function smush_get_stats(array $input): array|WP_Error
{
    unset($input);

    $ready = require_smush();
    if ($ready instanceof WP_Error) {
        return $ready;
    }

    return collect_stats();
}
