<?php

declare(strict_types=1);

namespace LayrShift\UpdraftPlus;

use WP_Error;

if (!defined('ABSPATH')) {
    exit();
}

function is_updraftplus_available(): bool
{
    return defined('UPDRAFTPLUS_VERSION') || class_exists('UpdraftPlus_Options', false);
}

/** @return true|WP_Error */
function require_updraftplus(): true|WP_Error
{
    if (!is_updraftplus_available()) {
        return new WP_Error('updraftplus_not_active', __('UpdraftPlus is not active on this site.', 'layrshift'));
    }

    return true;
}

/**
 * @return mixed
 */
function updraft_option(string $key, $default = null): mixed
{
    if (class_exists('UpdraftPlus_Options') && method_exists('UpdraftPlus_Options', 'get_updraft_option')) {
        return \UpdraftPlus_Options::get_updraft_option($key, $default);
    }

    return get_option('updraft_' . $key, $default);
}

/**
 * @return array<string, mixed>
 */
function get_backup_history(): array
{
    $history = updraft_option('backup_history', array());
    if (!is_array($history)) {
        return array();
    }

    return $history;
}

/**
 * @return array<string, mixed>
 */
function collect_status(): array
{
    $history = get_backup_history();
    $last_timestamp = null;

    foreach ($history as $timestamp => $backup) {
        if (!is_numeric($timestamp)) {
            continue;
        }
        $ts = (int) $timestamp;
        if ($last_timestamp === null || $ts > $last_timestamp) {
            $last_timestamp = $ts;
        }
    }

    return array(
        'version' => defined('UPDRAFTPLUS_VERSION') ? (string) UPDRAFTPLUS_VERSION : '',
        'backup_count' => count($history),
        'last_backup_timestamp' => $last_timestamp,
        'last_backup_human' => $last_timestamp ? gmdate('c', $last_timestamp) : null,
    );
}

/**
 * @return list<array<string, mixed>>
 */
function summarize_backups(int $limit = 20): array
{
    $history = get_backup_history();
    if ($history === array()) {
        return array();
    }

    krsort($history, SORT_NUMERIC);

    $items = array();
    foreach ($history as $timestamp => $backup) {
        if (count($items) >= $limit) {
            break;
        }

        if (!is_array($backup)) {
            continue;
        }

        $entities = array();
        foreach ($backup as $key => $value) {
            if (!is_string($key)) {
                continue;
            }
            if (in_array($key, array('nonce', 'service'), true)) {
                continue;
            }
            if (is_array($value) && isset($value[0])) {
                $entities[] = $key;
            }
        }

        $service = $backup['service'] ?? array();
        $destinations = array();
        if (is_array($service)) {
            foreach ($service as $dest) {
                if (is_string($dest) && $dest !== '') {
                    $destinations[] = $dest;
                }
            }
        } elseif (is_string($service) && $service !== '') {
            $destinations[] = $service;
        }

        $items[] = array(
            'timestamp' => is_numeric($timestamp) ? (int) $timestamp : null,
            'date' => is_numeric($timestamp) ? gmdate('c', (int) $timestamp) : null,
            'entities' => $entities,
            'destinations' => $destinations,
        );
    }

    return $items;
}

/**
 * @return array<string, mixed>
 */
function collect_settings(): array
{
    $interval = updraft_option('interval_backup');
    $retain   = updraft_option('retain');

    $entities = array(
        'files' => (bool) updraft_option('backup_files', 1),
        'database' => (bool) updraft_option('backup_database', 1),
    );

    $service = updraft_option('updraft_service', array());
    $destinations = array();
    if (is_array($service)) {
        foreach ($service as $dest) {
            if (is_string($dest) && $dest !== '') {
                $destinations[] = $dest;
            }
        }
    } elseif (is_string($service) && $service !== '') {
        $destinations[] = $service;
    }

    return array(
        'schedule_interval_hours' => is_numeric($interval) ? (int) $interval : null,
        'retain_backups' => is_numeric($retain) ? (int) $retain : null,
        'entities' => $entities,
        'destinations' => $destinations,
    );
}
