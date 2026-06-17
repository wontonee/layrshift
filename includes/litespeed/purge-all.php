<?php

declare(strict_types=1);

namespace LayrShift\LiteSpeed;

use WP_Error;

if (!defined('ABSPATH')) {
    exit();
}

wp_register_ability('layrshift/litespeed-purge-all', [
    'label' => __('Purge LiteSpeed Cache', 'layrshift'),
    'description' => __('Purge all LiteSpeed Cache entries.', 'layrshift'),
    'category' => 'layrshift-litespeed',
    'input_schema' => ['type' => 'object', 'properties' => (object) [], 'additionalProperties' => false],
    'execute_callback' => __NAMESPACE__ . '\\litespeed_purge_all',
    'permission_callback' => array(\LayrShift\Auth::class, 'check_ability_permission'),
    'meta' => ['mcp' => ['public' => true], 'annotations' => ['readonly' => false, 'destructive' => true, 'idempotent' => true]],
]);

/** @param array<string, mixed> $input */
function litespeed_purge_all(array $input): array|WP_Error
{
    unset($input);
    $ready = require_litespeed();
    if ($ready instanceof WP_Error) {
        return $ready;
    }
    return purge_all();
}
