<?php

declare(strict_types=1);

namespace LayrShift\MigrateGuru;

use WP_Error;

if (!defined('ABSPATH')) {
    exit();
}

function is_migrate_guru_available(): bool
{
    return defined('MG_VERSION')
        || defined('MG_PLUGIN_VERSION')
        || function_exists('mg_get_connection_key')
        || class_exists('MG');
}

/** @return true|WP_Error */
function require_migrate_guru(): true|WP_Error
{
    if (!is_migrate_guru_available()) {
        return new WP_Error('migrate_guru_not_active', __('Migrate Guru is not active on this site.', 'layrshift'));
    }

    return true;
}

/**
 * @return array<string, mixed>
 */
function collect_status(): array
{
    $version = '';
    if (defined('MG_VERSION')) {
        $version = (string) MG_VERSION;
    } elseif (defined('MG_PLUGIN_VERSION')) {
        $version = (string) MG_PLUGIN_VERSION;
    }

    $connection_key = get_option('mg_connection_key', '');
    if (!is_string($connection_key)) {
        $connection_key = '';
    }

    $activated = get_option('mg_activated', false);

    return array(
        'version' => $version,
        'connected' => $connection_key !== '' || !empty($activated),
        'site_url' => home_url('/'),
        'admin_url' => admin_url('admin.php?page=migrate-guru'),
        'has_connection_key' => $connection_key !== '',
    );
}

/**
 * @return array<string, mixed>
 */
function collect_connection_info(): array
{
    $key = get_option('mg_connection_key', '');
    if (!is_string($key)) {
        $key = '';
    }

    $masked = '';
    if ($key !== '') {
        $masked = str_repeat('*', max(0, strlen($key) - 4)) . substr($key, -4);
    }

    $account = get_option('mg_account_info', array());
    if (!is_array($account)) {
        $account = array();
    }

    $safe_account = array();
    foreach (array('email', 'plan', 'status') as $field) {
        if (isset($account[$field]) && is_scalar($account[$field])) {
            $safe_account[$field] = (string) $account[$field];
        }
    }

    return array(
        'connection_key_masked' => $masked,
        'account' => $safe_account,
        'activated' => (bool) get_option('mg_activated', false),
        'migrate_admin_url' => admin_url('admin.php?page=migrate-guru'),
    );
}

/**
 * @return array<string, mixed>
 */
function collect_migration_state(): array
{
    global $wpdb;

    $state = array(
        'migration_in_progress' => false,
        'last_migration' => null,
        'recent_options' => array(),
    );

    $in_progress = get_transient('mg_migration_in_progress');
    if ($in_progress) {
        $state['migration_in_progress'] = true;
    }

    $last = get_option('mg_last_migration', null);
    if (is_array($last)) {
        $state['last_migration'] = array_intersect_key(
            $last,
            array_flip(array('completed_at', 'status', 'source', 'destination'))
        );
    }

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery
    $rows = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE %s ORDER BY option_id DESC LIMIT %d",
            $wpdb->esc_like('mg_') . '%',
            15
        ),
        ARRAY_A
    );

    $redact = array('mg_connection_key', 'mg_secret', 'mg_api_key');
    foreach ((array) $rows as $row) {
        $name = (string) ($row['option_name'] ?? '');
        if ($name === '' || in_array($name, $redact, true)) {
            continue;
        }
        $value = $row['option_value'] ?? '';
        if (is_serialized($value)) {
            $unserialized = maybe_unserialize($value);
            $state['recent_options'][$name] = is_scalar($unserialized) ? $unserialized : '[complex]';
        } else {
            $state['recent_options'][$name] = is_string($value) && strlen($value) > 120
                ? substr($value, 0, 120) . '…'
                : $value;
        }
    }

    return $state;
}

/** @param array<string, mixed> $input */
function input_int(array $input, string $key, int $default): int
{
    if (!array_key_exists($key, $input)) {
        return $default;
    }

    return is_scalar($input[$key]) ? (int) $input[$key] : $default;
}
