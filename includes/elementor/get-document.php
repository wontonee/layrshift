<?php

// SPDX-FileCopyrightText: 2026 Ovation S.r.l. <dev@novamira.ai>
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace LayrShift\Elementor;

use WP_Error;

if (!defined('ABSPATH')) {
    exit();
}

wp_register_ability('layrshift/elementor-get-document', [
    'label' => __('Get Elementor Document', 'layrshift'),
    'description' => __(
        'Reads an Elementor document element tree and metadata for one post or template. Prefer this over execute-php when inspecting Elementor page structure.',
        'layrshift',
    ),
    'category' => 'layrshift-elementor',
    'input_schema' => [
        'type' => 'object',
        'properties' => [
            'post_id' => ['type' => 'integer', 'description' => 'Elementor document post ID. Alias: target_id.'],
            'target_id' => ['type' => 'integer', 'description' => 'Alias for post_id.'],
            'max_depth' => [
                'type' => 'integer',
                'description' => 'Maximum nested elements depth to include in the compact tree.',
                'default' => 6,
            ],
            'include_settings' => [
                'type' => 'boolean',
                'description' => 'Whether to include element settings in the compact tree.',
                'default' => true,
            ],
        ],
        'anyOf' => [
            ['required' => ['post_id']],
            ['required' => ['target_id']],
        ],
        'additionalProperties' => false,
    ],
    'output_schema' => [
        'type' => 'object',
        'properties' => [
            'post_id' => ['type' => 'integer'],
            'post_type' => ['type' => 'string'],
            'post_status' => ['type' => 'string'],
            'post_title' => ['type' => 'string'],
            'edit_mode' => ['type' => 'string'],
            'template_type' => ['type' => 'string'],
            'elementor_version' => ['type' => 'string'],
            'elements' => ['type' => 'array'],
            'json_valid' => ['type' => 'boolean'],
            'page_settings' => ['type' => 'object'],
            'element_count' => ['type' => 'integer'],
        ],
    ],
    'execute_callback' => __NAMESPACE__ . '\\elementor_get_document',
    'permission_callback' => array(\LayrShift\Auth::class, 'check_ability_permission'),
    'meta' => [
        'show_in_rest' => true,
        'mcp' => ['public' => true],
        'annotations' => [
            'instructions' => 'Use before and after Elementor edits to verify structure. For Theme Builder header/footer work, list templates with elementor-list-templates first, then get-document on the template post ID.',
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
function elementor_get_document(array $input): array|WP_Error
{
    $post_id = input_post_id($input);
    $max_depth = max(0, input_int($input, 'max_depth', 6));
    $include_settings = input_bool($input, 'include_settings', true);

    $document = get_elementor_document($post_id);
    if ($document instanceof WP_Error) {
        return $document;
    }

    $post = get_post($post_id);
    if (!$post instanceof \WP_Post) {
        return new WP_Error(
            'elementor_document_not_found',
            sprintf(
                /* translators: %d: post ID */
                __('Post %d was not found.', 'layrshift'),
                $post_id
            )
        );
    }

    $elements = $document->get_elements_data();
    if (!is_array($elements)) {
        $elements = array();
    }

    $raw = get_post_meta($post_id, '_elementor_data', true);
    $decoded = is_string($raw) ? json_decode($raw, true) : null;

    $page_settings = $document->get_settings();
    if (!is_array($page_settings)) {
        $page_settings = array();
    }

    return array(
        'post_id' => $post_id,
        'post_type' => $post->post_type,
        'post_status' => $post->post_status,
        'post_title' => $post->post_title,
        'edit_mode' => (string) get_post_meta($post_id, '_elementor_edit_mode', true),
        'template_type' => (string) get_post_meta($post_id, '_elementor_template_type', true),
        'elementor_version' => defined('ELEMENTOR_VERSION') ? (string) ELEMENTOR_VERSION : '',
        'elements' => shape_elements($elements, $max_depth, $include_settings),
        'json_valid' => is_array($decoded) && json_last_error() === JSON_ERROR_NONE,
        'page_settings' => $include_settings ? $page_settings : array(),
        'element_count' => count_element_nodes($elements),
    );
}
