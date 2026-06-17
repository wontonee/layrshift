<?php

declare(strict_types=1);

namespace LayrShift\ContactForm7;

use WP_Error;

if (!defined('ABSPATH')) {
    exit();
}

wp_register_ability('layrshift/contact-form-7-get-status', [
    'label' => __('Get Contact Form 7 Status', 'layrshift'),
    'description' => __('Read Contact Form 7 version and published form count.', 'layrshift'),
    'category' => 'layrshift-contact-form-7',
    'input_schema' => [
        'type' => 'object',
        'properties' => (object) [],
        'additionalProperties' => false,
    ],
    'execute_callback' => __NAMESPACE__ . '\\contact_form_7_get_status',
    'permission_callback' => array(\LayrShift\Auth::class, 'check_ability_permission'),
    'meta' => [
        'mcp' => ['public' => true],
        'annotations' => ['readonly' => true, 'destructive' => false, 'idempotent' => true],
    ],
]);

/** @param array<string, mixed> $input */
function contact_form_7_get_status(array $input): array|WP_Error
{
    unset($input);

    $ready = require_contact_form_7();
    if ($ready instanceof WP_Error) {
        return $ready;
    }

    return collect_status();
}
