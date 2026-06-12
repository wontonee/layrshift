<?php

// SPDX-FileCopyrightText: 2026 Ovation S.r.l. <dev@novamira.ai>
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace LayrShift\Elementor;

use WP_Error;

if (!defined('ABSPATH')) {
    exit();
}

wp_register_ability('layrshift/elementor-list-templates', [
    'label' => __('List Elementor Templates', 'layrshift'),
    'description' => __(
        'Lists Elementor library templates (header, footer, single, archive, etc.) with post IDs and template types.',
        'layrshift',
    ),
    'category' => 'layrshift-elementor',
    'input_schema' => [
        'type' => 'object',
        'properties' => [
            'template_type' => [
                'type' => 'string',
                'description' => 'Optional filter by _elementor_template_type (e.g. header, footer, single).',
            ],
            'posts_per_page' => [
                'type' => 'integer',
                'description' => 'Maximum templates to return.',
                'default' => 50,
            ],
        ],
        'additionalProperties' => false,
    ],
    'output_schema' => [
        'type' => 'object',
        'properties' => [
            'templates' => ['type' => 'array'],
            'count' => ['type' => 'integer'],
        ],
    ],
    'execute_callback' => __NAMESPACE__ . '\\elementor_list_templates',
    'permission_callback' => array(\LayrShift\Auth::class, 'check_ability_permission'),
    'meta' => [
        'show_in_rest' => true,
        'mcp' => ['public' => true],
        'annotations' => [
            'instructions' => 'Use before editing site header/footer or Theme Builder templates. Edit the template post returned here, not arbitrary pages.',
            'readonly' => true,
            'destructive' => false,
            'idempotent' => true,
        ],
    ],
]);

/**
 * @param array<string, mixed> $input
 * @return array<string, mixed>|WP_Error
 */
function elementor_list_templates(array $input): array|WP_Error
{
    $ready = require_elementor();
    if ($ready instanceof WP_Error) {
        return $ready;
    }

    $posts_per_page = max(1, min(200, input_int($input, 'posts_per_page', 50)));
    $template_type = isset($input['template_type']) && is_scalar($input['template_type'])
        ? sanitize_key((string) $input['template_type'])
        : '';

    $query_args = array(
        'post_type' => 'elementor_library',
        'post_status' => array('publish', 'draft', 'private'),
        'posts_per_page' => $posts_per_page,
        'orderby' => 'modified',
        'order' => 'DESC',
        'meta_query' => array(
            array(
                'key' => '_elementor_template_type',
                'compare' => 'EXISTS',
            ),
        ),
    );

    if ($template_type !== '') {
        $query_args['meta_query'][] = array(
            'key' => '_elementor_template_type',
            'value' => $template_type,
        );
    }

    $posts = get_posts($query_args);
    $templates = array();

    foreach ($posts as $post) {
        if (!$post instanceof \WP_Post) {
            continue;
        }

        $templates[] = array(
            'id' => $post->ID,
            'title' => $post->post_title,
            'status' => $post->post_status,
            'template_type' => (string) get_post_meta($post->ID, '_elementor_template_type', true),
            'edit_mode' => (string) get_post_meta($post->ID, '_elementor_edit_mode', true),
        );
    }

    return array(
        'templates' => $templates,
        'count' => count($templates),
    );
}
