<?php

declare(strict_types=1);

namespace LayrShift\WpRocket;

use WP_Error;

if (!defined('ABSPATH')) {
    exit();
}

function is_wp_rocket_available(): bool
{
    return defined('WP_ROCKET_VERSION') || function_exists('rocket_clean_domain');
}

/** @return true|WP_Error */
function require_wp_rocket(): true|WP_Error
{
    if (!is_wp_rocket_available()) {
        return new WP_Error('wp_rocket_not_active', __('WP Rocket is not active on this site.', 'layrshift'));
    }

    return true;
}

/**
 * @return array<string, mixed>
 */
function collect_status(): array
{
    $settings = get_option('wp_rocket_settings', array());
    if (!is_array($settings)) {
        $settings = array();
    }

    return array(
        'version' => defined('WP_ROCKET_VERSION') ? (string) WP_ROCKET_VERSION : '',
        'cache_enabled' => !empty($settings['cache_mobile']) || !empty($settings['cache_logged_user']) || !empty($settings['cache_reject_uri']),
        'minify_css' => !empty($settings['minify_css']),
        'minify_js' => !empty($settings['minify_concatenate_js']) || !empty($settings['minify_js']),
        'lazyload' => !empty($settings['lazyload']),
        'cdn_enabled' => !empty($settings['cdn']),
        'can_clear_cache' => function_exists('rocket_clean_domain'),
    );
}

/**
 * @return array<string, mixed>
 */
function collect_settings(): array
{
    $settings = get_option('wp_rocket_settings', array());
    if (!is_array($settings)) {
        $settings = array();
    }

    $safe_keys = array(
        'cache_mobile',
        'cache_logged_user',
        'cache_ssl',
        'minify_css',
        'minify_js',
        'minify_concatenate_js',
        'lazyload',
        'defer_all_js',
        'delay_js',
        'cdn',
        'manual_preload',
        'automatic_cleanup_frequency',
    );

    $subset = array();
    foreach ($safe_keys as $key) {
        if (array_key_exists($key, $settings)) {
            $subset[$key] = $settings[$key];
        }
    }

    return array(
        'version' => defined('WP_ROCKET_VERSION') ? (string) WP_ROCKET_VERSION : '',
        'settings' => $subset,
    );
}

/**
 * @return array<string, mixed>|WP_Error
 */
function clear_cache(): array|WP_Error
{
    $cleared = array();

    if (function_exists('rocket_clean_domain')) {
        rocket_clean_domain();
        $cleared[] = 'domain';
    }

    if (function_exists('rocket_clean_minify')) {
        rocket_clean_minify();
        $cleared[] = 'minify';
    }

    if (function_exists('rocket_clean_cache_busting')) {
        rocket_clean_cache_busting();
        $cleared[] = 'cache_busting';
    }

    if ($cleared === array()) {
        return new WP_Error('wp_rocket_clear_unavailable', __('WP Rocket cache clear functions are not available.', 'layrshift'));
    }

    return array(
        'success' => true,
        'cleared' => $cleared,
        'message' => __('WP Rocket cache cleared.', 'layrshift'),
    );
}
