<?php

declare(strict_types=1);

namespace LayrShift\Blogibot;

use WP_Error;

if (!defined('ABSPATH')) {
    exit();
}

function is_blogibot_available(): bool
{
    return defined('BLOGIBOT_VERSION')
        || class_exists('BlogiBot\\Plugin')
        || class_exists('Blogibot\\Plugin')
        || function_exists('blogibot');
}

/**
 * @return true|WP_Error
 */
function require_blogibot(): true|WP_Error
{
    if (!is_blogibot_available()) {
        return new WP_Error('blogibot_not_active', __('BlogiBot is not active on this site.', 'layrshift'));
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
 * @return list<string>
 */
function detect_post_types(): array
{
    $types = array_filter(
        get_post_types(array('public' => false), 'names'),
        static fn(string $type): bool => str_contains(strtolower($type), 'blogibot')
            || str_contains(strtolower($type), 'blogi')
    );

    if ($types !== array()) {
        return array_values($types);
    }

    return array('post');
}

/**
 * @return array<string, mixed>
 */
function read_settings(): array
{
    $options = array();
    foreach (array('blogibot_settings', 'blogibot_options', 'blogi_bot_settings') as $key) {
        $value = get_option($key);
        if (is_array($value)) {
            $options[$key] = $value;
        }
    }

    return $options;
}
