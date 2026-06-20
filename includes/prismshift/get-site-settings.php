<?php

declare(strict_types=1);

namespace LayrShift\PrismShift;

use PrismShift\Core\Settings;
use WP_Error;

if (!defined('ABSPATH')) {
    exit();
}

wp_register_ability('layrshift/prismshift-get-site-settings', [
    'label' => __('Get PrismShift Site SEO Settings', 'layrshift'),
    'description' => __('Read PrismShift global SEO options (separator, home SEO, sitemap, breadcrumbs).', 'layrshift'),
    'category' => 'layrshift-prismshift',
    'input_schema' => [
        'type' => 'object',
        'properties' => (object) [],
        'additionalProperties' => false,
    ],
    'execute_callback' => __NAMESPACE__ . '\\prismshift_get_site_settings',
    'permission_callback' => array(\LayrShift\Auth::class, 'check_ability_permission'),
    'meta' => [
        'mcp' => ['public' => true],
        'annotations' => ['readonly' => true, 'destructive' => false, 'idempotent' => true],
    ],
]);

/** @param array<string, mixed> $input */
function prismshift_get_site_settings(array $input): array|WP_Error
{
    unset($input);

    $ready = require_prismshift();
    if ($ready instanceof WP_Error) {
        return $ready;
    }

    $public = Settings::get_public();

    return array(
        'prismshift_version' => defined('PRISMSHIFT_VERSION') ? (string) PRISMSHIFT_VERSION : '',
        'title_separator' => (string) ($public['title_separator'] ?? ''),
        'home_title' => (string) ($public['home_title'] ?? ''),
        'home_description' => (string) ($public['home_description'] ?? ''),
        'org_name' => (string) ($public['org_name'] ?? ''),
        'sitemap_enabled' => !empty($public['sitemap_enabled']),
        'breadcrumbs_enabled' => !empty($public['breadcrumbs_enabled']),
        'setup_complete' => !empty($public['setup_complete']),
    );
}
