<?php

declare(strict_types=1);

namespace LayrShift\RankMath;

use WP_Error;
use WP_Post;

if (!defined('ABSPATH')) {
    exit();
}

function is_rank_math_available(): bool
{
    return defined('RANK_MATH_VERSION') || class_exists('RankMath');
}

/** @return true|WP_Error */
function require_rank_math(): true|WP_Error
{
    if (!is_rank_math_available()) {
        return new WP_Error('rank_math_not_active', __('Rank Math SEO is not active on this site.', 'layrshift'));
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
        return new WP_Error('rank_math_invalid_post_id', __('A valid post_id is required.', 'layrshift'));
    }

    $post = get_post($post_id);
    if (!$post instanceof WP_Post) {
        return new WP_Error('rank_math_post_not_found', sprintf(
            /* translators: %d: post ID */
            __('Post %d was not found.', 'layrshift'),
            $post_id
        ));
    }

    return $post;
}

/**
 * @return array<string, mixed>
 */
function read_post_seo(int $post_id): array
{
    $title    = (string) get_post_meta($post_id, 'rank_math_title', true);
    $desc     = (string) get_post_meta($post_id, 'rank_math_description', true);
    $focus_kw = (string) get_post_meta($post_id, 'rank_math_focus_keyword', true);
    $robots   = get_post_meta($post_id, 'rank_math_robots', true);

    if (!is_array($robots)) {
        $robots = array();
    }

    return array(
        'post_id' => $post_id,
        'raw' => array(
            'seo_title' => $title,
            'meta_description' => $desc,
            'focus_keyword' => $focus_kw,
            'robots' => $robots,
        ),
    );
}

/**
 * @param array<string, mixed> $fields
 */
function write_post_seo(int $post_id, array $fields): true|WP_Error
{
    $map = array(
        'seo_title' => 'rank_math_title',
        'meta_description' => 'rank_math_description',
        'focus_keyword' => 'rank_math_focus_keyword',
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

    if (array_key_exists('robots', $fields) && is_array($fields['robots'])) {
        update_post_meta($post_id, 'rank_math_robots', $fields['robots']);
        $updated = true;
    }

    if (!$updated) {
        return new WP_Error('rank_math_no_fields', __('No Rank Math SEO fields were provided to update.', 'layrshift'));
    }

    // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Rank Math hook.
    do_action('rank_math/admin/save_post', $post_id);

    return true;
}
