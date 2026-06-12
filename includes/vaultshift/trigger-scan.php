<?php

declare(strict_types=1);

namespace LayrShift\VaultShift;

use VaultShift\Core\Scheduler;
use WP_Error;

if (!defined('ABSPATH')) {
    exit();
}

wp_register_ability('layrshift/vaultshift-trigger-scan', [
    'label' => __('Trigger VaultShift Scan', 'layrshift'),
    'description' => __('Schedule a VaultShift malware/security scan.', 'layrshift'),
    'category' => 'layrshift-vaultshift',
    'input_schema' => [
        'type' => 'object',
        'properties' => (object) [],
        'additionalProperties' => false,
    ],
    'execute_callback' => __NAMESPACE__ . '\\vaultshift_trigger_scan',
    'permission_callback' => array(\LayrShift\Auth::class, 'check_ability_permission'),
    'meta' => [
        'mcp' => ['public' => true],
        'annotations' => ['destructive' => false],
    ],
]);

/** @param array<string, mixed> $input */
function vaultshift_trigger_scan(array $input): array|WP_Error
{
    unset($input);

    $ready = require_vaultshift();
    if ($ready instanceof WP_Error) {
        return $ready;
    }

    if (!class_exists(Scheduler::class)) {
        return new WP_Error('vaultshift_scan_unavailable', __('VaultShift scheduler is not available.', 'layrshift'));
    }

    Scheduler::schedule_scan();

    return array(
        'scheduled' => true,
        'message' => __('VaultShift scan scheduled.', 'layrshift'),
    );
}
