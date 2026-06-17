<?php

declare(strict_types=1);

namespace LayrShift\Wordfence;

use WP_Error;

if (!defined('ABSPATH')) {
    exit();
}

function is_wordfence_available(): bool
{
    return defined('WORDFENCE_VERSION') || class_exists('wordfence', false);
}

/** @return true|WP_Error */
function require_wordfence(): true|WP_Error
{
    if (!is_wordfence_available()) {
        return new WP_Error('wordfence_not_active', __('Wordfence is not active on this site.', 'layrshift'));
    }

    return true;
}

/**
 * @return string|int|bool|null
 */
function wf_config(string $key, $default = null): mixed
{
    if (class_exists('wfConfig') && method_exists('wfConfig', 'get')) {
        return \wfConfig::get($key, $default);
    }

    return get_option('wordfence_' . $key, $default);
}

/**
 * @return array<string, mixed>
 */
function collect_status(): array
{
    $firewall_enabled = wf_config('firewallEnabled');
    $learning_mode    = wf_config('learningModeGracePeriodEnabled');

    $is_premium = null;
    if (class_exists('wfLicense') && method_exists('wfLicense', 'current')) {
        try {
            $license = \wfLicense::current();
            if (is_object($license) && method_exists($license, 'isPaidAndCurrent')) {
                $is_premium = (bool) $license->isPaidAndCurrent();
            }
        } catch (\Throwable $e) {
            $is_premium = null;
        }
    }

    return array(
        'version' => defined('WORDFENCE_VERSION') ? (string) WORDFENCE_VERSION : '',
        'firewall_enabled' => (bool) $firewall_enabled,
        'learning_mode' => (bool) $learning_mode,
        'waf_status' => (string) wf_config('wafStatus', ''),
        'is_premium' => $is_premium,
    );
}

/**
 * @return array<string, mixed>|WP_Error
 */
function collect_scan_summary(): array|WP_Error
{
    $last_scan = wf_config('lastScanTime');
    $issues    = wf_config('totalIssues');

    if ($last_scan === null && $issues === null && !class_exists('wordfence')) {
        return new WP_Error(
            'wordfence_scan_unavailable',
            __('Wordfence scan summary is not available on this installation.', 'layrshift')
        );
    }

    return array(
        'last_scan_time' => is_numeric($last_scan) ? (int) $last_scan : null,
        'last_scan_human' => is_numeric($last_scan) ? gmdate('c', (int) $last_scan) : null,
        'total_issues' => is_numeric($issues) ? (int) $issues : null,
        'scan_running' => (bool) wf_config('scanRunning', false),
    );
}

/**
 * @return array<string, mixed>
 */
function collect_settings_summary(): array
{
    return array(
        'live_traffic_enabled' => (bool) wf_config('liveTrafficEnabled', false),
        'alert_on_login_lockout' => (bool) wf_config('alertOn_loginLockout', false),
        'disable_application_passwords' => (bool) wf_config('disableApplicationPasswords', false),
        'two_factor_enabled' => (bool) wf_config('loginSecurityEnabled', false),
        'block_countries' => (bool) wf_config('isPaid', false),
    );
}
