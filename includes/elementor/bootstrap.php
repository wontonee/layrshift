<?php

// SPDX-FileCopyrightText: 2026 Ovation S.r.l. <dev@novamira.ai>
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace LayrShift\Elementor;

use Elementor\Core\Base\Document;
use WP_Error;
use WP_Post;

if (!defined('ABSPATH')) {
    exit();
}

/**
 * @return true|WP_Error
 */
function require_elementor(): true|WP_Error
{
    if (!defined('ELEMENTOR_VERSION') || !class_exists('\Elementor\Plugin')) {
        return new WP_Error(
            'elementor_not_active',
            __('Elementor is not active on this site.', 'layrshift')
        );
    }

    return true;
}

/** @param array<string, mixed> $input */
function input_post_id(array $input): int
{
    if (array_key_exists('post_id', $input)) {
        return is_scalar($input['post_id']) ? (int) $input['post_id'] : 0;
    }

    if (array_key_exists('target_id', $input)) {
        return is_scalar($input['target_id']) ? (int) $input['target_id'] : 0;
    }

    return 0;
}

function get_target_post(int $post_id): WP_Post|WP_Error
{
    if ($post_id <= 0) {
        return new WP_Error('elementor_invalid_post_id', __('A valid post_id is required.', 'layrshift'));
    }

    $post = get_post($post_id);
    if (!$post instanceof WP_Post) {
        return new WP_Error(
            'elementor_document_not_found',
            sprintf(__('Post %d was not found.', 'layrshift'), $post_id)
        );
    }

    return $post;
}

/**
 * @return Document|WP_Error
 */
function get_elementor_document(int $post_id): Document|WP_Error
{
    $ready = require_elementor();
    if ($ready instanceof WP_Error) {
        return $ready;
    }

    $post = get_target_post($post_id);
    if ($post instanceof WP_Error) {
        return $post;
    }

    $document = \Elementor\Plugin::$instance->documents->get($post_id, false);
    if (!$document instanceof Document) {
        return new WP_Error(
            'elementor_document_not_found',
            sprintf(__('Elementor document for post %d was not found.', 'layrshift'), $post_id)
        );
    }

    return $document;
}

/**
 * @param array<int, array<string, mixed>> $elements
 * @return array<int, array<string, mixed>>
 */
function shape_elements(array $elements, int $max_depth, bool $include_settings, int $depth = 0): array
{
    $shaped = [];

    foreach ($elements as $element) {
        if (!is_array($element)) {
            continue;
        }

        $entry = array(
            'id' => $element['id'] ?? null,
            'elType' => $element['elType'] ?? null,
        );

        if (($element['elType'] ?? '') === 'widget') {
            $entry['widgetType'] = $element['widgetType'] ?? null;
        }

        if ($include_settings && isset($element['settings']) && is_array($element['settings'])) {
            $entry['settings'] = $element['settings'];
        }

        $children = is_array($element['elements'] ?? null) ? $element['elements'] : array();
        if ($depth < $max_depth && $children !== array()) {
            $entry['elements'] = shape_elements($children, $max_depth, $include_settings, $depth + 1);
        } elseif ($children !== array()) {
            $entry['element_count'] = count_element_nodes($children);
        }

        $shaped[] = $entry;
    }

    return $shaped;
}

/**
 * @param array<int, array<string, mixed>> $elements
 */
function count_element_nodes(array $elements): int
{
    $count = 0;

    foreach ($elements as $element) {
        if (!is_array($element)) {
            continue;
        }

        ++$count;
        $children = is_array($element['elements'] ?? null) ? $element['elements'] : array();
        if ($children !== array()) {
            $count += count_element_nodes($children);
        }
    }

    return $count;
}

/**
 * @param array<int, array<string, mixed>> $elements
 * @return true|WP_Error
 */
function validate_elements_tree(array $elements, string $path = 'elements'): true|WP_Error
{
    if ($elements === array()) {
        return new WP_Error(
            'elementor_invalid_elements',
            __('The elements array must contain at least one element.', 'layrshift')
        );
    }

    foreach ($elements as $index => $element) {
        if (!is_array($element)) {
            return new WP_Error(
                'elementor_invalid_elements',
                sprintf(__('%s[%d] must be an object.', 'layrshift'), $path, $index)
            );
        }

        $id = $element['id'] ?? null;
        if (!is_string($id) || $id === '') {
            return new WP_Error(
                'elementor_invalid_elements',
                sprintf(__('%s[%d].id must be a non-empty string.', 'layrshift'), $path, $index)
            );
        }

        $el_type = $element['elType'] ?? null;
        if (!is_string($el_type) || $el_type === '') {
            return new WP_Error(
                'elementor_invalid_elements',
                sprintf(__('%s[%d].elType must be a non-empty string.', 'layrshift'), $path, $index)
            );
        }

        if ($el_type === 'widget') {
            $widget_type = $element['widgetType'] ?? null;
            if (!is_string($widget_type) || $widget_type === '') {
                return new WP_Error(
                    'elementor_invalid_elements',
                    sprintf(__('%s[%d].widgetType must be a non-empty string for widgets.', 'layrshift'), $path, $index)
                );
            }
        }

        $children = $element['elements'] ?? array();
        if ($children !== array()) {
            if (!is_array($children)) {
                return new WP_Error(
                    'elementor_invalid_elements',
                    sprintf(__('%s[%d].elements must be an array.', 'layrshift'), $path, $index)
                );
            }

            $child_path = sprintf('%s[%d].elements', $path, $index);
            $valid = validate_elements_tree($children, $child_path);
            if ($valid instanceof WP_Error) {
                return $valid;
            }
        }
    }

    return true;
}

/**
 * @param array<string, mixed> $input
 */
function input_bool(array $input, string $key, bool $default): bool
{
    if (!array_key_exists($key, $input)) {
        return $default;
    }

    return (bool) $input[$key];
}

/**
 * @param array<string, mixed> $input
 */
function input_int(array $input, string $key, int $default): int
{
    if (!array_key_exists($key, $input)) {
        return $default;
    }

    return is_scalar($input[$key]) ? (int) $input[$key] : $default;
}
