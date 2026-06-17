<?php

declare(strict_types=1);

namespace LayrShift\UpdraftPlus;

use WP_Error;

if (!defined('ABSPATH')) {
    exit();
}

wp_register_ability('layrshift/updraftplus-list-backups', [
    'label' => __('List UpdraftPlus Backups', 'layrshift'),
    'description' => __('List recent UpdraftPlus backup sets (dates, entities, destination types — no credentials).', 'layrshift'),
    'category' => 'layrshift-updraftplus',
    'input_schema' => [
        'type' => 'object',
        'properties' => [
            'limit' => ['type' => 'integer', 'default' => 20, 'minimum' => 1, 'maximum' => 50],
        ],
        'additionalProperties' => false,
    ],
    'execute_callback' => __NAMESPACE__ . '\\updraftplus_list_backups',
    'permission_callback' => array(\LayrShift\Auth::class, 'check_ability_permission'),
    'meta' => [
        'mcp' => ['public' => true],
        'annotations' => ['readonly' => true, 'destructive' => false, 'idempotent' => true],
    ],
]);

/** @param array<string, mixed> $input */
function updraftplus_list_backups(array $input): array|WP_Error
{
    $ready = require_updraftplus();
    if ($ready instanceof WP_Error) {
        return $ready;
    }

    $limit = isset($input['limit']) && is_numeric($input['limit']) ? max(1, min(50, (int) $input['limit'])) : 20;

    return array(
        'backups' => summarize_backups($limit),
        'total' => count(get_backup_history()),
    );
}
