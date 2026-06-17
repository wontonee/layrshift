<?php

declare(strict_types=1);

namespace LayrShift\WpOptimize;

use WP_Error;

if (!defined('ABSPATH')) {
    exit();
}

function is_wp_optimize_available(): bool
{
    return defined('WPO_VERSION') || class_exists('WP_Optimize');
}

/** @return true|WP_Error */
function require_wp_optimize(): true|WP_Error
{
    if (!is_wp_optimize_available()) {
        return new WP_Error('wp_optimize_not_active', __('WP-Optimize is not active on this site.', 'layrshift'));
    }

    return true;
}

/**
 * @return array<string, mixed>
 */
function collect_status(): array
{
    $options = get_option('wp-optimize-settings', array());
    if (!is_array($options)) {
        $options = array();
    }

    $page_cache = get_option('wpo_cache_config', array());
    if (!is_array($page_cache)) {
        $page_cache = array();
    }

    return array(
        'version' => defined('WPO_VERSION') ? (string) WPO_VERSION : '',
        'page_cache_enabled' => !empty($page_cache['enable_page_caching']),
        'minify_enabled' => !empty($options['enable_minify']),
        'gzip_compression' => !empty($options['enable_site_compression']),
        'can_purge_cache' => function_exists('wpo_cache_flush') || class_exists('WP_Optimize'),
    );
}

/**
 * @return array<string, mixed>
 */
function collect_settings(): array
{
    $options = get_option('wp-optimize-settings', array());
    if (!is_array($options)) {
        $options = array();
    }

    $page_cache = get_option('wpo_cache_config', array());
    if (!is_array($page_cache)) {
        $page_cache = array();
    }

    return array(
        'version' => defined('WPO_VERSION') ? (string) WPO_VERSION : '',
        'optimize_settings' => array_intersect_key(
            $options,
            array_flip(array('enable_minify', 'enable_site_compression', 'retention-enabled', 'retention-period'))
        ),
        'page_cache' => array_intersect_key(
            $page_cache,
            array_flip(array('enable_page_caching', 'enable_per_role_cache', 'enable_mobile_caching'))
        ),
    );
}

/**
 * @return array<string, mixed>|WP_Error
 */
function purge_cache(): array|WP_Error
{
    $cleared = array();

    if (function_exists('wpo_cache_flush')) {
        wpo_cache_flush();
        $cleared[] = 'page_cache';
    } elseif (class_exists('WP_Optimize') && method_exists('WP_Optimize', 'get_page_cache')) {
        $instance = \WP_Optimize::instance();
        if (method_exists($instance, 'get_page_cache')) {
            $cache = $instance->get_page_cache();
            if ($cache && method_exists($cache, 'purge')) {
                $cache->purge();
                $cleared[] = 'page_cache';
            }
        }
    }

    if (class_exists('WP_Optimize') && method_exists('WP_Optimize', 'get_minify')) {
        $minify = \WP_Optimize::instance()->get_minify();
        if ($minify && method_exists($minify, 'purge')) {
            $minify->purge();
            $cleared[] = 'minify';
        }
    }

    if ($cleared === array()) {
        return new WP_Error('wp_optimize_purge_unavailable', __('WP-Optimize cache purge is not available.', 'layrshift'));
    }

    return array(
        'success' => true,
        'cleared' => $cleared,
        'message' => __('WP-Optimize cache purged.', 'layrshift'),
    );
}
