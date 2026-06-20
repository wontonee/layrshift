<?php

declare(strict_types=1);

namespace LayrShift\PrismShift;

use WP_Error;

if (!defined('ABSPATH')) {
    exit();
}

wp_register_ability('layrshift/prismshift-get-post-seo', [
    'label' => __('Get PrismShift Post SEO', 'layrshift'),
    'description' => __('Read PrismShift SEO title, meta description, focus keyword, and robots settings for one post.', 'layrshift'),
    'category' => 'layrshift-prismshift',
    'input_schema' => [
        'type' => 'object',
        'properties' => [
            'post_id' => ['type' => 'integer'],
            'target_id' => ['type' => 'integer'],
        ],
        'anyOf' => [
            ['required' => ['post_id']],
            ['required' => ['target_id']],
        ],
        'additionalProperties' => false,
    ],
    'execute_callback' => __NAMESPACE__ . '\\prismshift_get_post_seo',
    'permission_callback' => array(\LayrShift\Auth::class, 'check_ability_permission'),
    'meta' => [
        'mcp' => ['public' => true],
        'annotations' => ['readonly' => true, 'destructive' => false, 'idempotent' => true],
    ],
]);

/** @param array<string, mixed> $input */
function prismshift_get_post_seo(array $input): array|WP_Error
{
    $ready = require_prismshift();
    if ($ready instanceof WP_Error) {
        return $ready;
    }

    $post_id = input_post_id($input);
    $post = get_target_post($post_id);
    if ($post instanceof WP_Error) {
        return $post;
    }

    $data = read_post_seo($post_id);
    $data['post_type'] = $post->post_type;
    $data['post_status'] = $post->post_status;
    $data['post_title'] = $post->post_title;

    return $data;
}
