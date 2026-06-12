<?php

declare(strict_types=1);

namespace LayrShift\Smush;

use WP_Error;

if (!defined('ABSPATH')) {
    exit();
}

wp_register_ability('layrshift/smush-list-unsmushed', [
    'label' => __('List Unsmushed Images', 'layrshift'),
    'description' => __('List image attachments that have not been optimized by Smush yet.', 'layrshift'),
    'category' => 'layrshift-smush',
    'input_schema' => [
        'type' => 'object',
        'properties' => [
            'limit' => ['type' => 'integer', 'default' => 20],
        ],
        'additionalProperties' => false,
    ],
    'execute_callback' => __NAMESPACE__ . '\\smush_list_unsmushed',
    'permission_callback' => array(\LayrShift\Auth::class, 'check_ability_permission'),
    'meta' => [
        'mcp' => ['public' => true],
        'annotations' => ['readonly' => true, 'destructive' => false, 'idempotent' => true],
    ],
]);

/** @param array<string, mixed> $input */
function smush_list_unsmushed(array $input): array|WP_Error
{
    $ready = require_smush();
    if ($ready instanceof WP_Error) {
        return $ready;
    }

    $limit = input_int($input, 'limit', 20);

    return list_unsmushed_media($limit);
}
