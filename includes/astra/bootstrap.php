<?php

declare(strict_types=1);

namespace LayrShift\Astra;

use WP_Error;

if (!defined('ABSPATH')) {
    exit();
}

function is_astra_available(): bool
{
    return defined('ASTRA_THEME_VERSION') || get_template() === 'astra';
}

/** @return true|WP_Error */
function require_astra(): true|WP_Error
{
    if (!is_astra_available()) {
        return new WP_Error('astra_not_active', __('Astra theme is not active on this site.', 'layrshift'));
    }

    return true;
}

/**
 * @return array<string, mixed>
 */
function collect_status(): array
{
    $theme = wp_get_theme();

    return array(
        'astra_version' => defined('ASTRA_THEME_VERSION') ? (string) ASTRA_THEME_VERSION : '',
        'astra_pro' => defined('ASTRA_EXT_VER') ? (string) ASTRA_EXT_VER : null,
        'astra_addon' => defined('ASTRA_ADDON_VER') ? (string) ASTRA_ADDON_VER : null,
        'theme_name' => $theme->get('Name'),
        'stylesheet' => get_stylesheet(),
        'is_child_theme' => (bool) $theme->parent(),
    );
}

/**
 * @return array<string, mixed>
 */
function collect_settings(): array
{
    $settings = get_option('astra-settings', array());
    if (!is_array($settings)) {
        $settings = array();
    }

    $safe_keys = array(
        'theme-color',
        'link-color',
        'text-color',
        'body-font-family',
        'headings-font-family',
        'site-layout',
        'site-content-width',
        'narrow-container-width',
        'sidebar-width',
        'header-main-layout-width',
        'footer-sml-layout',
        'blog-layout',
        'blog-width',
        'single-post-ast-content-layout',
        'page-layout',
    );

    $subset = array();
    foreach ($safe_keys as $key) {
        if (array_key_exists($key, $settings)) {
            $subset[$key] = $settings[$key];
        }
    }

    return array(
        'settings' => $subset,
        'theme_mods_count' => count(get_theme_mods() ?: array()),
    );
}

/**
 * @return list<string>
 */
function header_footer_post_types(): array
{
    $types = array('astra-advanced-hook', 'astra_hook');
    $found = array();

    foreach ($types as $post_type) {
        if (post_type_exists($post_type)) {
            $found[] = $post_type;
        }
    }

    return $found;
}

/**
 * @return array<string, mixed>
 */
function collect_header_footer(): array
{
    $post_types = header_footer_post_types();
    $items = array();

    foreach ($post_types as $post_type) {
        $posts = get_posts(
            array(
                'post_type' => $post_type,
                'post_status' => array('publish', 'draft'),
                'posts_per_page' => 50,
                'orderby' => 'title',
                'order' => 'ASC',
            )
        );

        foreach ($posts as $post) {
            $hook = (string) get_post_meta($post->ID, 'ast-advanced-hook-location', true);
            if ($hook === '') {
                $hook = (string) get_post_meta($post->ID, 'ast-advanced-hook-layout', true);
            }

            $items[] = array(
                'id' => $post->ID,
                'title' => $post->post_title,
                'status' => $post->post_status,
                'post_type' => $post_type,
                'hook' => $hook,
            );
        }
    }

    return array(
        'post_types' => $post_types,
        'items' => $items,
        'count' => count($items),
    );
}
