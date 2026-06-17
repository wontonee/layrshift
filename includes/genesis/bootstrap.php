<?php

declare(strict_types=1);

namespace LayrShift\Genesis;

use WP_Error;
use WP_Post;

if (!defined('ABSPATH')) {
    exit();
}

function is_genesis_available(): bool
{
    return function_exists('genesis') || get_template() === 'genesis';
}

/** @return true|WP_Error */
function require_genesis(): true|WP_Error
{
    if (!is_genesis_available()) {
        return new WP_Error('genesis_not_active', __('Genesis Framework is not active on this site.', 'layrshift'));
    }

    return true;
}

function settings_option_name(): string
{
    if (defined('GENESIS_SETTINGS_FIELD')) {
        return (string) GENESIS_SETTINGS_FIELD;
    }

    return 'genesis-settings';
}

/**
 * @return array<string, mixed>
 */
function collect_status(): array
{
    $theme = wp_get_theme();
    $parent = $theme->parent();

    $layouts = array();
    if (function_exists('genesis_get_layouts')) {
        $layouts = genesis_get_layouts('site');
        if (!is_array($layouts)) {
            $layouts = array();
        }
    }

    return array(
        'genesis_version' => defined('PARENT_THEME_VERSION') ? (string) PARENT_THEME_VERSION : '',
        'parent_theme'    => $parent ? $parent->get('Name') : $theme->get('Name'),
        'child_theme'     => $parent ? $theme->get('Name') : null,
        'stylesheet'      => get_stylesheet(),
        'template'        => get_template(),
        'layout_count'    => count($layouts),
        'layouts'         => array_keys($layouts),
    );
}

/**
 * @return array<string, mixed>
 */
function collect_settings(): array
{
    $options = get_option(settings_option_name(), array());
    if (!is_array($options)) {
        $options = array();
    }

    $safe_keys = array(
        'nav_extras',
        'nav_superfish',
        'nav_shrink',
        'content_archive',
        'content_archive_limit',
        'content_archive_thumbnail',
        'image_size',
        'image_alignment',
        'posts_nav',
        'breadcrumb_home',
        'breadcrumb_front_page',
        'breadcrumb_single',
        'breadcrumb_page',
        'breadcrumb_404',
        'breadcrumb_attachment',
        'footer_text',
        'footer_scripts',
    );

    $subset = array();
    foreach ($safe_keys as $key) {
        if (array_key_exists($key, $options)) {
            $subset[$key] = $options[$key];
        }
    }

    return array(
        'settings' => $subset,
    );
}

function get_target_post(int $post_id): WP_Post|WP_Error
{
    if ($post_id <= 0) {
        return new WP_Error('genesis_invalid_post_id', __('A valid post_id is required.', 'layrshift'));
    }

    $post = get_post($post_id);
    if (!$post instanceof WP_Post) {
        return new WP_Error('genesis_post_not_found', sprintf(
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
function read_post_meta(int $post_id): array
{
    return array(
        'post_id' => $post_id,
        'layout' => (string) get_post_meta($post_id, '_genesis_layout', true),
        'hide_title' => (bool) get_post_meta($post_id, '_genesis_hide_title', true),
        'hide_breadcrumbs' => (bool) get_post_meta($post_id, '_genesis_hide_breadcrumbs', true),
        'hide_singular_image' => (bool) get_post_meta($post_id, '_genesis_hide_singular_image', true),
        'hide_footer_widgets' => (bool) get_post_meta($post_id, '_genesis_hide_footer_widgets', true),
        'custom_body_class' => (string) get_post_meta($post_id, '_genesis_custom_body_class', true),
        'custom_post_class' => (string) get_post_meta($post_id, '_genesis_custom_post_class', true),
        'seo' => array(
            'title' => (string) get_post_meta($post_id, '_genesis_title', true),
            'description' => (string) get_post_meta($post_id, '_genesis_description', true),
            'canonical_uri' => (string) get_post_meta($post_id, '_genesis_canonical_uri', true),
            'noindex' => (bool) get_post_meta($post_id, '_genesis_noindex', true),
            'nofollow' => (bool) get_post_meta($post_id, '_genesis_nofollow', true),
        ),
    );
}
