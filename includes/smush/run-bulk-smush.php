<?php

declare(strict_types=1);

namespace LayrShift\Smush;

use WP_Error;

if (!defined('ABSPATH')) {
    exit();
}

wp_register_ability('layrshift/smush-run-bulk-smush', [
    'label' => __('Run Smush Bulk Optimization', 'layrshift'),
    'description' => __('Queue or run Smush bulk image optimization. Confirm with the user before running on production-sized libraries.', 'layrshift'),
    'category' => 'layrshift-smush',
    'input_schema' => [
        'type' => 'object',
        'properties' => [
            'limit' => [
                'type' => 'integer',
                'description' => 'Optional batch size hint when Smush supports batched bulk runs.',
                'default' => 50,
            ],
        ],
        'additionalProperties' => false,
    ],
    'execute_callback' => __NAMESPACE__ . '\\smush_run_bulk_smush',
    'permission_callback' => array(\LayrShift\Auth::class, 'check_ability_permission'),
    'meta' => [
        'mcp' => ['public' => true],
        'annotations' => ['destructive' => true],
    ],
]);

/** @param array<string, mixed> $input */
function smush_run_bulk_smush(array $input): array|WP_Error
{
    $ready = require_smush();
    if ($ready instanceof WP_Error) {
        return $ready;
    }

    $limit = input_int($input, 'limit', 50);
    $queued = false;
    $message = '';

    if (class_exists('WP_Smush') && is_callable(array('WP_Smush', 'get_instance'))) {
        $instance = \WP_Smush::get_instance();
        if (isset($instance->core) && is_object($instance->core) && method_exists($instance->core, 'bulk_smush_handle')) {
            $instance->core->bulk_smush_handle();
            $queued = true;
            $message = 'bulk_smush_handle invoked';
        }
    }

    if (!$queued) {
        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Smush plugin hook.
        do_action('wp_smush_bulk_smush_start');
        $queued = true;
        $message = 'wp_smush_bulk_smush_start action fired';
    }

    return array(
        'queued' => $queued,
        'message' => $message,
        'limit_hint' => $limit,
        'pending' => list_unsmushed_media(min($limit, 20)),
    );
}
