<?php

declare(strict_types=1);

namespace LayrShift\VaultShift;

use VaultShift\Security\ActivityLog;
use WP_Error;

if (!defined('ABSPATH')) {
    exit();
}

wp_register_ability('layrshift/vaultshift-list-activity', [
    'label' => __('List VaultShift Activity', 'layrshift'),
    'description' => __('Read recent VaultShift security activity log entries.', 'layrshift'),
    'category' => 'layrshift-vaultshift',
    'input_schema' => [
        'type' => 'object',
        'properties' => [
            'limit' => ['type' => 'integer', 'default' => 20],
        ],
        'additionalProperties' => false,
    ],
    'execute_callback' => __NAMESPACE__ . '\\vaultshift_list_activity',
    'permission_callback' => array(\LayrShift\Auth::class, 'check_ability_permission'),
    'meta' => [
        'mcp' => ['public' => true],
        'annotations' => ['readonly' => true, 'destructive' => false, 'idempotent' => true],
    ],
]);

/** @param array<string, mixed> $input */
function vaultshift_list_activity(array $input): array|WP_Error
{
    $ready = require_vaultshift();
    if ($ready instanceof WP_Error) {
        return $ready;
    }

    if (!class_exists(ActivityLog::class)) {
        return new WP_Error('vaultshift_activity_unavailable', __('VaultShift activity log is not available.', 'layrshift'));
    }

    $limit = max(1, min(100, input_int($input, 'limit', 20)));
    $rows = ActivityLog::get_recent($limit);

    return array(
        'count' => count($rows),
        'items' => array_map(
            static function ($row): array {
                return array(
                    'event_type' => (string) ($row->event_type ?? ''),
                    'severity' => (string) ($row->severity ?? ''),
                    'description' => (string) ($row->description ?? ''),
                    'user_login' => (string) ($row->user_login ?? ''),
                    'ip_address' => (string) ($row->ip_address ?? ''),
                    'created_at' => (string) ($row->created_at ?? ''),
                );
            },
            $rows
        ),
    );
}
