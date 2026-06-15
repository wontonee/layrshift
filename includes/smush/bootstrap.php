<?php

declare(strict_types=1);

namespace LayrShift\Smush;

use WP_Error;

if (!defined('ABSPATH')) {
    exit();
}

function is_smush_available(): bool
{
    return defined('WP_SMUSH_VERSION') || class_exists('WP_Smush');
}

/**
 * @return true|WP_Error
 */
function require_smush(): true|WP_Error
{
    if (!is_smush_available()) {
        return new WP_Error('smush_not_active', __('Smush is not active on this site.', 'layrshift'));
    }

    return true;
}

/** @param array<string, mixed> $input */
function input_int(array $input, string $key, int $default): int
{
    if (!array_key_exists($key, $input)) {
        return $default;
    }

    return is_scalar($input[$key]) ? (int) $input[$key] : $default;
}

/**
 * @return array<string, mixed>
 */
function collect_stats(): array
{
    $stats = get_option('wp-smush-stats', array());
    if (!is_array($stats)) {
        $stats = array();
    }

    $settings = get_option('wp-smush-settings', array());
    if (!is_array($settings)) {
        $settings = array();
    }

    return array(
        'smush_version' => defined('WP_SMUSH_VERSION') ? (string) WP_SMUSH_VERSION : '',
        'stats' => $stats,
        'settings' => array(
            'auto' => !empty($settings['auto']),
            'lossy' => !empty($settings['lossy']),
            'strip_exif' => !empty($settings['strip_exif']),
            'lazy_load' => !empty($settings['lazy_load']),
        ),
    );
}

/**
 * @return array{count: int, items: list<array{id: int, title: string, mime_type: string}>}
 */
function list_unsmushed_media(int $limit): array
{
    global $wpdb;

    $limit = max(1, min(100, $limit));

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery
    $like = $wpdb->esc_like( 'image/' ) . '%';
    $ids = $wpdb->get_col(
        $wpdb->prepare(
            "SELECT p.ID
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = 'wp-smush-lossy'
            WHERE p.post_type = 'attachment'
              AND p.post_mime_type LIKE %s
              AND pm.meta_id IS NULL
            ORDER BY p.ID DESC
            LIMIT %d",
            $like,
            $limit
        )
    );

    $items = array();
    foreach ((array) $ids as $id) {
        $attachment_id = (int) $id;
        $items[] = array(
            'id' => $attachment_id,
            'title' => (string) get_the_title($attachment_id),
            'mime_type' => (string) get_post_mime_type($attachment_id),
        );
    }

    return array(
        'count' => count($items),
        'items' => $items,
    );
}
