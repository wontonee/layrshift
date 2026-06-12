<?php

declare(strict_types=1);

namespace LayrShift\Yoast;

use WP_Error;
use WP_Post;

if (!defined('ABSPATH')) {
    exit();
}

function is_yoast_available(): bool
{
    return defined('WPSEO_VERSION') || function_exists('YoastSEO');
}

/**
 * @return true|WP_Error
 */
function require_yoast(): true|WP_Error
{
    if (!is_yoast_available()) {
        return new WP_Error('yoast_not_active', __('Yoast SEO is not active on this site.', 'layrshift'));
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
        return new WP_Error('yoast_invalid_post_id', __('A valid post_id is required.', 'layrshift'));
    }

    $post = get_post($post_id);
    if (!$post instanceof WP_Post) {
        return new WP_Error('yoast_post_not_found', sprintf(__('Post %d was not found.', 'layrshift'), $post_id));
    }

    return $post;
}

/**
 * @return array<string, mixed>
 */
function read_post_seo(int $post_id): array
{
    $raw_title = (string) get_post_meta($post_id, '_yoast_wpseo_title', true);
    $raw_desc = (string) get_post_meta($post_id, '_yoast_wpseo_metadesc', true);
    $focus_kw = (string) get_post_meta($post_id, '_yoast_wpseo_focuskw', true);
    $noindex = (string) get_post_meta($post_id, '_yoast_wpseo_meta-robots-noindex', true);

    $resolved = array(
        'title' => $raw_title,
        'description' => $raw_desc,
    );

    if (function_exists('YoastSEO')) {
        try {
            $meta = YoastSEO()->meta->for_post($post_id);
            if ($meta) {
                $resolved['title'] = (string) ($meta->title ?? $raw_title);
                $resolved['description'] = (string) ($meta->description ?? $raw_desc);
                $resolved['canonical'] = (string) ($meta->canonical ?? '');
                $resolved['robots'] = array(
                    'noindex' => (bool) ($meta->robots['noindex'] ?? false),
                    'nofollow' => (bool) ($meta->robots['nofollow'] ?? false),
                );
            }
        } catch (\Throwable $e) {
            $resolved['surface_error'] = $e->getMessage();
        }
    }

    return array(
        'post_id' => $post_id,
        'raw' => array(
            'seo_title' => $raw_title,
            'meta_description' => $raw_desc,
            'focus_keyword' => $focus_kw,
            'noindex' => $noindex,
        ),
        'resolved' => $resolved,
    );
}

/**
 * @param array<string, mixed> $fields
 */
function write_post_seo(int $post_id, array $fields): true|WP_Error
{
    $map = array(
        'seo_title' => '_yoast_wpseo_title',
        'meta_description' => '_yoast_wpseo_metadesc',
        'focus_keyword' => '_yoast_wpseo_focuskw',
        'noindex' => '_yoast_wpseo_meta-robots-noindex',
    );

    $updated = false;
    foreach ($map as $key => $meta_key) {
        if (!array_key_exists($key, $fields)) {
            continue;
        }
        $value = $fields[$key];
        if (!is_scalar($value)) {
            continue;
        }
        update_post_meta($post_id, $meta_key, (string) $value);
        $updated = true;
    }

    if (!$updated) {
        return new WP_Error('yoast_no_fields', __('No Yoast SEO fields were provided to update.', 'layrshift'));
    }

    if (function_exists('wpseo_save_postdata')) {
        wpseo_save_postdata($post_id);
    }

    do_action('wpseo_save_indexable', $post_id, get_post($post_id), true);

    return true;
}
