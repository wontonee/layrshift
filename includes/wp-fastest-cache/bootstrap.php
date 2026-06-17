<?php

declare(strict_types=1);

namespace LayrShift\WpFastestCache;

use WP_Error;

if (!defined('ABSPATH')) {
    exit();
}

function is_wp_fastest_cache_available(): bool
{
    return class_exists('WpFastestCache');
}

/** @return true|WP_Error */
function require_wp_fastest_cache(): true|WP_Error
{
    if (!is_wp_fastest_cache_available()) {
        return new WP_Error('wp_fastest_cache_not_active', __('WP Fastest Cache is not active on this site.', 'layrshift'));
    }

    return true;
}

/**
 * @return array<string, mixed>
 */
function collect_status(): array
{
    $options = get_option('WpFastestCache', '');
    $decoded = is_string($options) ? json_decode($options, true) : $options;
    if (!is_array($decoded)) {
        $decoded = array();
    }

    return array(
        'version' => defined('WPFC_WP_CONTENT_URL') ? 'active' : 'active',
        'cache_enabled' => !empty($decoded['wpFastestCacheStatus']),
        'mobile_cache' => !empty($decoded['wpFastestCacheMobile']),
        'gzip' => !empty($decoded['wpFastestCacheGzip']),
        'browser_cache' => !empty($decoded['wpFastestCacheLBC']),
        'can_clear_cache' => class_exists('WpFastestCache'),
    );
}

/**
 * @return array<string, mixed>
 */
function collect_settings(): array
{
    $options = get_option('WpFastestCache', '');
    $decoded = is_string($options) ? json_decode($options, true) : $options;
    if (!is_array($decoded)) {
        $decoded = array();
    }

    $safe_keys = array(
        'wpFastestCacheStatus',
        'wpFastestCacheMobile',
        'wpFastestCacheGzip',
        'wpFastestCacheLBC',
        'wpFastestCacheMinifyHtml',
        'wpFastestCacheMinifyCss',
        'wpFastestCacheCombineCss',
        'wpFastestCacheCombineJs',
        'wpFastestCacheLazyLoad',
    );

    $subset = array();
    foreach ($safe_keys as $key) {
        if (array_key_exists($key, $decoded)) {
            $subset[$key] = $decoded[$key];
        }
    }

    return array('settings' => $subset);
}

/**
 * @return array<string, mixed>|WP_Error
 */
function clear_cache(): array|WP_Error
{
    $cache = new \WpFastestCache();
    $cleared = array();

    if (method_exists($cache, 'deleteCache')) {
        $cache->deleteCache(true);
        $cleared[] = 'html';
    }

    if (method_exists($cache, 'deleteCssAndJsCache')) {
        $cache->deleteCssAndJsCache();
        $cleared[] = 'css_js';
    }

    if ($cleared === array()) {
        return new WP_Error('wp_fastest_cache_clear_unavailable', __('WP Fastest Cache clear methods are not available.', 'layrshift'));
    }

    return array(
        'success' => true,
        'cleared' => $cleared,
        'message' => __('WP Fastest Cache cleared.', 'layrshift'),
    );
}
