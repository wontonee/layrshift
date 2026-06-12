<?php

// SPDX-FileCopyrightText: 2026 Ovation S.r.l. <dev@novamira.ai>
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace LayrShift\Elementor;

use WP_Error;

if (!defined('ABSPATH')) {
    exit();
}

wp_register_ability('layrshift/elementor-save-document', [
    'label' => __('Save Elementor Document', 'layrshift'),
    'description' => __(
        'Saves an Elementor element tree to a document via the Elementor Document API. Defaults to draft-only; set allow_publish true only when the user explicitly approves live edits.',
        'layrshift',
    ),
    'category' => 'layrshift-elementor',
    'input_schema' => [
        'type' => 'object',
        'properties' => [
            'post_id' => ['type' => 'integer', 'description' => 'Elementor document post ID. Alias: target_id.'],
            'target_id' => ['type' => 'integer', 'description' => 'Alias for post_id.'],
            'elements' => [
                'type' => 'array',
                'description' => 'Full Elementor elements array. Each node needs id, elType, and widgetType for widgets.',
            ],
            'page_settings' => [
                'type' => 'object',
                'description' => 'Optional Elementor page/document settings object.',
            ],
            'allow_publish' => [
                'type' => 'boolean',
                'description' => 'Allow saving to published posts. Default false (draft-only safety).',
                'default' => false,
            ],
        ],
        'anyOf' => [
            ['required' => ['post_id', 'elements']],
            ['required' => ['target_id', 'elements']],
        ],
        'additionalProperties' => false,
    ],
    'output_schema' => [
        'type' => 'object',
        'properties' => [
            'post_id' => ['type' => 'integer'],
            'post_status' => ['type' => 'string'],
            'saved' => ['type' => 'boolean'],
            'element_count' => ['type' => 'integer'],
        ],
    ],
    'execute_callback' => __NAMESPACE__ . '\\elementor_save_document',
    'permission_callback' => array(\LayrShift\Auth::class, 'check_ability_permission'),
    'meta' => [
        'show_in_rest' => true,
        'mcp' => ['public' => true],
        'annotations' => [
            'instructions' => 'Duplicate to draft before structural edits unless the user approved live changes. Preserve element id values from elementor-get-document. Re-read with elementor-get-document after saving.',
            'readonly' => false,
            'destructive' => true,
            'idempotent' => false,
        ],
    ],
]);

/**
 * @param array<string, mixed> $input
 * @return array<string, mixed>|WP_Error
 */
function elementor_save_document(array $input): array|WP_Error
{
    $post_id = input_post_id($input);
    $allow_publish = input_bool($input, 'allow_publish', false);
    $elements = $input['elements'] ?? null;

    if (!is_array($elements)) {
        return new WP_Error(
            'elementor_invalid_elements',
            __('The elements field must be an array.', 'layrshift')
        );
    }

    $valid = validate_elements_tree($elements);
    if ($valid instanceof WP_Error) {
        return $valid;
    }

    $post = get_target_post($post_id);
    if ($post instanceof WP_Error) {
        return $post;
    }

    if ($post->post_status === 'publish' && !$allow_publish) {
        return new WP_Error(
            'elementor_publish_not_allowed',
            __('Saving published Elementor documents requires allow_publish=true and explicit user approval.', 'layrshift'),
            array('post_id' => $post_id, 'post_status' => $post->post_status)
        );
    }

    $document = get_elementor_document($post_id);
    if ($document instanceof WP_Error) {
        return $document;
    }

    $payload = array('elements' => $elements);
    if (isset($input['page_settings']) && is_array($input['page_settings'])) {
        $payload['settings'] = $input['page_settings'];
    }

    $saved = $document->save($payload);
    if ($saved instanceof WP_Error) {
        return $saved;
    }

    if ($saved === false) {
        return new WP_Error(
            'elementor_save_failed',
            sprintf(__('Elementor failed to save document %d.', 'layrshift'), $post_id)
        );
    }

    $fresh = get_elementor_document($post_id);
    $fresh_elements = $fresh instanceof \Elementor\Core\Base\Document
        ? $fresh->get_elements_data()
        : array();

    return array(
        'post_id' => $post_id,
        'post_status' => get_post_status($post_id),
        'saved' => true,
        'element_count' => is_array($fresh_elements) ? count_element_nodes($fresh_elements) : 0,
    );
}
