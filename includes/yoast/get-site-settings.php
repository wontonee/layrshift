<?php

declare(strict_types=1);

namespace LayrShift\Yoast;

use WP_Error;

if (!defined('ABSPATH')) {
    exit();
}

wp_register_ability('layrshift/yoast-get-site-settings', [
    'label' => __('Get Yoast Site SEO Settings', 'layrshift'),
    'description' => __('Read key Yoast SEO global options (site name, separator, social defaults).', 'layrshift'),
    'category' => 'layrshift-yoast',
    'input_schema' => [
        'type' => 'object',
        'properties' => (object) [],
        'additionalProperties' => false,
    ],
    'execute_callback' => __NAMESPACE__ . '\\yoast_get_site_settings',
    'permission_callback' => array(\LayrShift\Auth::class, 'check_ability_permission'),
    'meta' => [
        'mcp' => ['public' => true],
        'annotations' => ['readonly' => true, 'destructive' => false, 'idempotent' => true],
    ],
]);

/** @param array<string, mixed> $input */
function yoast_get_site_settings(array $input): array|WP_Error
{
    unset($input);

    $ready = require_yoast();
    if ($ready instanceof WP_Error) {
        return $ready;
    }

    $titles = get_option('wpseo_titles', array());
    $social = get_option('wpseo_social', array());

    if (!is_array($titles)) {
        $titles = array();
    }
    if (!is_array($social)) {
        $social = array();
    }

    return array(
        'yoast_version' => defined('WPSEO_VERSION') ? (string) WPSEO_VERSION : '',
        'site_name' => (string) ($titles['website_name'] ?? get_bloginfo('name')),
        'separator' => (string) ($titles['separator'] ?? ''),
        'home_title' => (string) ($titles['title-home-wpseo'] ?? ''),
        'home_description' => (string) ($titles['metadesc-home-wpseo'] ?? ''),
        'social' => array(
            'facebook_site' => (string) ($social['facebook_site'] ?? ''),
            'twitter_site' => (string) ($social['twitter_site'] ?? ''),
        ),
    );
}
