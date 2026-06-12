<?php

declare(strict_types=1);

namespace LayrShift\Yoast;

use WP_Error;

if (!defined('ABSPATH')) {
    exit();
}

wp_register_ability('layrshift/yoast-update-post-seo', [
    'label' => __('Update Yoast Post SEO', 'layrshift'),
    'description' => __('Update Yoast SEO fields on a post. Defaults to draft-only unless allow_publish is true.', 'layrshift'),
    'category' => 'layrshift-yoast',
    'input_schema' => [
        'type' => 'object',
        'properties' => [
            'post_id' => ['type' => 'integer'],
            'target_id' => ['type' => 'integer'],
            'seo_title' => ['type' => 'string'],
            'meta_description' => ['type' => 'string'],
            'focus_keyword' => ['type' => 'string'],
            'noindex' => ['type' => 'string', 'description' => '1 to noindex, 0 or empty to index.'],
            'allow_publish' => ['type' => 'boolean', 'default' => false],
        ],
        'anyOf' => [
            ['required' => ['post_id']],
            ['required' => ['target_id']],
        ],
        'additionalProperties' => false,
    ],
    'execute_callback' => __NAMESPACE__ . '\\yoast_update_post_seo',
    'permission_callback' => array(\LayrShift\Auth::class, 'check_ability_permission'),
    'meta' => [
        'mcp' => ['public' => true],
        'annotations' => ['destructive' => true],
    ],
]);

/** @param array<string, mixed> $input */
function yoast_update_post_seo(array $input): array|WP_Error
{
    $ready = require_yoast();
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
            'yoast_publish_not_allowed',
            __('Updating Yoast SEO on published posts requires allow_publish=true and explicit user approval.', 'layrshift')
        );
    }

    $fields = array_intersect_key(
        $input,
        array_flip(array('seo_title', 'meta_description', 'focus_keyword', 'noindex'))
    );

    $written = write_post_seo($post_id, $fields);
    if ($written instanceof WP_Error) {
        return $written;
    }

    return read_post_seo($post_id);
}
