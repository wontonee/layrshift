<?php

declare(strict_types=1);

namespace LayrShift\RankMath;

use WP_Error;

if (!defined('ABSPATH')) {
    exit();
}

wp_register_ability('layrshift/rank-math-get-site-settings', [
    'label' => __('Get Rank Math Site SEO Settings', 'layrshift'),
    'description' => __('Read key Rank Math global SEO options.', 'layrshift'),
    'category' => 'layrshift-rank-math',
    'input_schema' => [
        'type' => 'object',
        'properties' => (object) [],
        'additionalProperties' => false,
    ],
    'execute_callback' => __NAMESPACE__ . '\\rank_math_get_site_settings',
    'permission_callback' => array(\LayrShift\Auth::class, 'check_ability_permission'),
    'meta' => [
        'mcp' => ['public' => true],
        'annotations' => ['readonly' => true, 'destructive' => false, 'idempotent' => true],
    ],
]);

/** @param array<string, mixed> $input */
function rank_math_get_site_settings(array $input): array|WP_Error
{
    unset($input);

    $ready = require_rank_math();
    if ($ready instanceof WP_Error) {
        return $ready;
    }

    $options = get_option('rank-math-options-general', array());
    $titles  = get_option('rank-math-options-titles', array());

    if (!is_array($options)) {
        $options = array();
    }
    if (!is_array($titles)) {
        $titles = array();
    }

    return array(
        'rank_math_version' => defined('RANK_MATH_VERSION') ? (string) RANK_MATH_VERSION : '',
        'site_name' => (string) ($titles['website_name'] ?? get_bloginfo('name')),
        'home_title' => (string) ($titles['homepage_title'] ?? ''),
        'home_description' => (string) ($titles['homepage_description'] ?? ''),
        'breadcrumbs' => !empty($options['breadcrumbs']),
        'sitemap' => !empty($options['sitemap']),
    );
}
