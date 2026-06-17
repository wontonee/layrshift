<?php

declare(strict_types=1);

namespace LayrShift\Smush;

use WP_Error;

if (!defined('ABSPATH')) {
    exit();
}

wp_register_ability('layrshift/smush-optimize-attachment', [
    'label' => __('Optimize Attachment with Smush', 'layrshift'),
    'description' => __('Run Smush optimization on a single media attachment by ID.', 'layrshift'),
    'category' => 'layrshift-smush',
    'input_schema' => [
        'type' => 'object',
        'properties' => [
            'attachment_id' => [
                'type' => 'integer',
                'description' => __('WordPress attachment post ID.', 'layrshift'),
            ],
        ],
        'required' => ['attachment_id'],
        'additionalProperties' => false,
    ],
    'execute_callback' => __NAMESPACE__ . '\\smush_optimize_attachment',
    'permission_callback' => array(\LayrShift\Auth::class, 'check_ability_permission'),
    'meta' => [
        'mcp' => ['public' => true],
        'annotations' => ['readonly' => false, 'destructive' => true, 'idempotent' => true],
    ],
]);

/** @param array<string, mixed> $input */
function smush_optimize_attachment(array $input): array|WP_Error
{
    $ready = require_smush();
    if ($ready instanceof WP_Error) {
        return $ready;
    }

    $attachment_id = input_int($input, 'attachment_id', 0);
    if ($attachment_id <= 0) {
        return new WP_Error('smush_invalid_attachment', __('attachment_id must be a positive integer.', 'layrshift'));
    }

    if (get_post_type($attachment_id) !== 'attachment') {
        return new WP_Error('smush_not_attachment', __('The provided ID is not a media attachment.', 'layrshift'));
    }

    if (class_exists('WP_Smush')) {
        $smush = \WP_Smush::get_instance();
        if (isset($smush->core) && method_exists($smush->core, 'smush_single_attachment')) {
            $smush->core->smush_single_attachment($attachment_id);
            return array(
                'success' => true,
                'attachment_id' => $attachment_id,
                'message' => __('Smush optimization queued for attachment.', 'layrshift'),
            );
        }
    }

    if (has_action('wp_smush_smush_request')) {
        do_action('wp_smush_smush_request', $attachment_id);
        return array(
            'success' => true,
            'attachment_id' => $attachment_id,
            'message' => __('Smush optimization action fired for attachment.', 'layrshift'),
        );
    }

    return new WP_Error('smush_optimize_unavailable', __('Smush single-attachment optimization is not available.', 'layrshift'));
}
