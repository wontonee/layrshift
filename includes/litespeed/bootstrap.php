<?php

declare(strict_types=1);

namespace LayrShift\LiteSpeed;

use WP_Error;

if (!defined('ABSPATH')) {
    exit();
}

function is_litespeed_available(): bool
{
    return defined('LSCWP_V') || class_exists('LiteSpeed\Core');
}

/** @return true|WP_Error */
function require_litespeed(): true|WP_Error
{
    if (!is_litespeed_available()) {
        return new WP_Error('litespeed_not_active', __('LiteSpeed Cache is not active on this site.', 'layrshift'));
    }

    return true;
}

/**
 * @return array<string, mixed>
 */
function collect_status(): array
{
    $options = get_option('litespeed-cache-conf', array());
    if (!is_array($options)) {
        $options = array();
    }

    return array(
        'version' => defined('LSCWP_V') ? (string) LSCWP_V : '',
        'cache_enabled' => !empty($options['cache']),
        'object_cache' => !empty($options['object']),
        'browser_cache' => !empty($options['cache-browser']),
        'css_minify' => !empty($options['css_minify']),
        'js_minify' => !empty($options['js_minify']),
        'can_purge' => class_exists('LiteSpeed\Purge') || has_action('litespeed_purge_all'),
    );
}

/**
 * @return array<string, mixed>
 */
function collect_settings(): array
{
    $options = get_option('litespeed-cache-conf', array());
    if (!is_array($options)) {
        $options = array();
    }

    $safe_keys = array(
        'cache',
        'cache-browser',
        'cache-mobile',
        'cache-login_cookie',
        'object',
        'object-kind',
        'css_minify',
        'js_minify',
        'html_minify',
        'media-lazy',
        'media-lazy_placeholder',
        'cdn',
        'cdn-quic',
    );

    $subset = array();
    foreach ($safe_keys as $key) {
        if (array_key_exists($key, $options)) {
            $subset[$key] = $options[$key];
        }
    }

    return array(
        'version' => defined('LSCWP_V') ? (string) LSCWP_V : '',
        'settings' => $subset,
    );
}

/**
 * @return array<string, mixed>|WP_Error
 */
function purge_all(): array|WP_Error
{
    if (class_exists('LiteSpeed\Purge')) {
        \LiteSpeed\Purge::purge_all();
        return array(
            'success' => true,
            'method' => 'LiteSpeed\\Purge::purge_all',
            'message' => __('LiteSpeed cache purged.', 'layrshift'),
        );
    }

    if (has_action('litespeed_purge_all')) {
        do_action('litespeed_purge_all');
        return array(
            'success' => true,
            'method' => 'litespeed_purge_all',
            'message' => __('LiteSpeed cache purge action fired.', 'layrshift'),
        );
    }

    return new WP_Error('litespeed_purge_unavailable', __('LiteSpeed purge is not available.', 'layrshift'));
}
