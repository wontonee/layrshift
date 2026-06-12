<?php

declare(strict_types=1);

namespace LayrShift\Blogibot;

use WP_Error;

if (!defined('ABSPATH')) {
    exit();
}

wp_register_ability('layrshift/blogibot-list-posts', [
    'label' => __('List BlogiBot Posts', 'layrshift'),
    'description' => __('List recent posts from BlogiBot-managed post types (or standard posts as fallback).', 'layrshift'),
    'category' => 'layrshift-blogibot',
    'input_schema' => [
        'type' => 'object',
        'properties' => [
            'post_type' => ['type' => 'string'],
            'status' => ['type' => 'string', 'default' => 'any'],
            'limit' => ['type' => 'integer', 'default' => 20],
        ],
        'additionalProperties' => false,
    ],
    'execute_callback' => __NAMESPACE__ . '\\blogibot_list_posts',
    'permission_callback' => array(\LayrShift\Auth::class, 'check_ability_permission'),
    'meta' => [
        'mcp' => ['public' => true],
        'annotations' => ['readonly' => true, 'destructive' => false, 'idempotent' => true],
    ],
]);

/** @param array<string, mixed> $input */
function blogibot_list_posts(array $input): array|WP_Error
{
    $ready = require_blogibot();
    if ($ready instanceof WP_Error) {
        return $ready;
    }

    $detected = detect_post_types();
    $post_type = isset($input['post_type']) && is_scalar($input['post_type'])
        ? sanitize_key((string) $input['post_type'])
        : ($detected[0] ?? 'post');

    $status = isset($input['status']) && is_scalar($input['status'])
        ? sanitize_key((string) $input['status'])
        : 'any';

    $limit = max(1, min(100, input_int($input, 'limit', 20)));

    $posts = get_posts(array(
        'post_type' => $post_type,
        'post_status' => $status,
        'posts_per_page' => $limit,
        'orderby' => 'modified',
        'order' => 'DESC',
    ));

    $items = array();
    foreach ($posts as $post) {
        $items[] = array(
            'id' => $post->ID,
            'title' => $post->post_title,
            'status' => $post->post_status,
            'post_type' => $post->post_type,
            'modified' => $post->post_modified_gmt,
        );
    }

    return array(
        'post_type' => $post_type,
        'count' => count($items),
        'items' => $items,
    );
}
