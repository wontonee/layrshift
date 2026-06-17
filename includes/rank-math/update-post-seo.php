<?php

declare(strict_types=1);

namespace LayrShift\RankMath;

use WP_Error;

if (!defined('ABSPATH')) {
    exit();
}

wp_register_ability('layrshift/rank-math-update-post-seo', [
    'label' => __('Update Rank Math Post SEO', 'layrshift'),
    'description' => __('Update Rank Math SEO fields on a post. Defaults to draft-only unless allow_publish is true.', 'layrshift'),
    'category' => 'layrshift-rank-math',
    'input_schema' => [
        'type' => 'object',
        'properties' => [
            'post_id' => ['type' => 'integer'],
            'target_id' => ['type' => 'integer'],
            'seo_title' => ['type' => 'string'],
            'meta_description' => ['type' => 'string'],
            'focus_keyword' => ['type' => 'string'],
            'robots' => [
                'type' => 'array',
                'items' => ['type' => 'string'],
            ],
            'allow_publish' => ['type' => 'boolean', 'default' => false],
        ],
        'anyOf' => [
            ['required' => ['post_id']],
            ['required' => ['target_id']],
        ],
        'additionalProperties' => false,
    ],
    'execute_callback' => __NAMESPACE__ . '\\rank_math_update_post_seo',
    'permission_callback' => array(\LayrShift\Auth::class, 'check_ability_permission'),
    'meta' => [
        'mcp' => ['public' => true],
        'annotations' => ['destructive' => true],
    ],
]);

/** @param array<string, mixed> $input */
function rank_math_update_post_seo(array $input): array|WP_Error
{
    $ready = require_rank_math();
    if ($ready instanceof WP_Error) {
        return $ready;
    }

    $post_id = input_post_id($input);
    $post = get_target_post($post_id);
    if ($post instanceof WP_Error) {
        return $post;
    }

    $allow_publish = !empty($input['allow_publish']);
    if ($post->post_status === 'publish' && !$allow_publish) {
        return new WP_Error(
            'rank_math_publish_not_allowed',
            __('Updating Rank Math SEO on published posts requires allow_publish=true and explicit user approval.', 'layrshift')
        );
    }

    $fields = array_intersect_key(
        $input,
        array_flip(array('seo_title', 'meta_description', 'focus_keyword', 'robots'))
    );

    $written = write_post_seo($post_id, $fields);
    if ($written instanceof WP_Error) {
        return $written;
    }

    return read_post_seo($post_id);
}
