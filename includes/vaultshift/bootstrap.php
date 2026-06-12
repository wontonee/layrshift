<?php

declare(strict_types=1);

namespace LayrShift\VaultShift;

use WP_Error;

if (!defined('ABSPATH')) {
    exit();
}

function is_vaultshift_available(): bool
{
    return defined('VAULTSHIFT_VERSION') || class_exists('\VaultShift\Core\Plugin');
}

/**
 * @return true|WP_Error
 */
function require_vaultshift(): true|WP_Error
{
    if (!is_vaultshift_available()) {
        return new WP_Error('vaultshift_not_active', __('VaultShift is not active on this site.', 'layrshift'));
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
