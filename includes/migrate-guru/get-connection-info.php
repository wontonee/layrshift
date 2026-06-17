<?php

declare(strict_types=1);

namespace LayrShift\MigrateGuru;

use WP_Error;

if (!defined('ABSPATH')) {
    exit();
}

wp_register_ability('layrshift/migrate-guru-get-connection-info', [
    'label' => __('Get Migrate Guru Connection Info', 'layrshift'),
    'description' => __('Read masked Migrate Guru connection details (no raw secrets).', 'layrshift'),
    'category' => 'layrshift-migrate-guru',
    'input_schema' => ['type' => 'object', 'properties' => (object) [], 'additionalProperties' => false],
    'execute_callback' => __NAMESPACE__ . '\\migrate_guru_get_connection_info',
    'permission_callback' => array(\LayrShift\Auth::class, 'check_ability_permission'),
    'meta' => ['mcp' => ['public' => true], 'annotations' => ['readonly' => true, 'destructive' => false, 'idempotent' => true]],
]);

/** @param array<string, mixed> $input */
function migrate_guru_get_connection_info(array $input): array|WP_Error
{
    unset($input);
    $ready = require_migrate_guru();
    if ($ready instanceof WP_Error) {
        return $ready;
    }
    return collect_connection_info();
}
