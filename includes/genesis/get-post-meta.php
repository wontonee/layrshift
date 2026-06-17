<?php

declare(strict_types=1);

namespace LayrShift\Genesis;

use WP_Error;

if (!defined('ABSPATH')) {
    exit();
}

wp_register_ability('layrshift/genesis-get-post-meta', [
    'label' => __('Get Genesis Post Meta', 'layrshift'),
    'description' => __('Read Genesis per-post layout, visibility toggles, custom classes, and SEO meta.', 'layrshift'),
    'category' => 'layrshift-genesis',
    'input_schema' => [
        'type' => 'object',
        'properties' => [
            'post_id' => ['type' => 'integer'],
        ],
        'required' => ['post_id'],
        'additionalProperties' => false,
    ],
    'execute_callback' => __NAMESPACE__ . '\\genesis_get_post_meta',
    'permission_callback' => array(\LayrShift\Auth::class, 'check_ability_permission'),
    'meta' => [
        'mcp' => ['public' => true],
        'annotations' => ['readonly' => true, 'destructive' => false, 'idempotent' => true],
    ],
]);

/** @param array<string, mixed> $input */
function genesis_get_post_meta(array $input): array|WP_Error
{
    $ready = require_genesis();
    if ($ready instanceof WP_Error) {
        return $ready;
    }

    $post_id = isset($input['post_id']) && is_scalar($input['post_id']) ? (int) $input['post_id'] : 0;
    $post = get_target_post($post_id);
    if ($post instanceof WP_Error) {
        return $post;
    }

    $data = read_post_meta($post_id);
    $data['post_type'] = $post->post_type;
    $data['post_status'] = $post->post_status;
    $data['post_title'] = $post->post_title;

    return $data;
}
