<?php

declare(strict_types=1);

namespace LayrShift\VaultShift;

use VaultShift\Admin\Dashboard;
use VaultShift\Core\Settings;
use WP_Error;

if (!defined('ABSPATH')) {
    exit();
}

wp_register_ability('layrshift/vaultshift-get-status', [
    'label' => __('Get VaultShift Status', 'layrshift'),
    'description' => __('Read VaultShift security score, WAF mode, and version.', 'layrshift'),
    'category' => 'layrshift-vaultshift',
    'input_schema' => [
        'type' => 'object',
        'properties' => (object) [],
        'additionalProperties' => false,
    ],
    'execute_callback' => __NAMESPACE__ . '\\vaultshift_get_status',
    'permission_callback' => array(\LayrShift\Auth::class, 'check_ability_permission'),
    'meta' => [
        'mcp' => ['public' => true],
        'annotations' => ['readonly' => true, 'destructive' => false, 'idempotent' => true],
    ],
]);

/** @param array<string, mixed> $input */
function vaultshift_get_status(array $input): array|WP_Error
{
    unset($input);

    $ready = require_vaultshift();
    if ($ready instanceof WP_Error) {
        return $ready;
    }

    $settings = Settings::get();

    return array(
        'version' => defined('VAULTSHIFT_VERSION') ? (string) VAULTSHIFT_VERSION : '',
        'security_score' => Dashboard::calculate_security_score(),
        'waf_mode' => (string) ($settings['waf_mode'] ?? 'learning'),
        'is_pro' => class_exists('\VaultShift\Core\License') ? \VaultShift\Core\License::is_pro() : false,
        'last_scan' => class_exists('\VaultShift\Security\Scanner')
            ? \VaultShift\Security\Scanner::get_last_scan_summary()
            : null,
    );
}
