<?php

declare(strict_types=1);

namespace LayrShift\ContactForm7;

use WP_Error;

if (!defined('ABSPATH')) {
    exit();
}

wp_register_ability('layrshift/contact-form-7-get-form', [
    'label' => __('Get Contact Form 7 Form', 'layrshift'),
    'description' => __('Read a Contact Form 7 form markup and mail settings by ID.', 'layrshift'),
    'category' => 'layrshift-contact-form-7',
    'input_schema' => [
        'type' => 'object',
        'properties' => [
            'form_id' => ['type' => 'integer'],
        ],
        'required' => ['form_id'],
        'additionalProperties' => false,
    ],
    'execute_callback' => __NAMESPACE__ . '\\contact_form_7_get_form',
    'permission_callback' => array(\LayrShift\Auth::class, 'check_ability_permission'),
    'meta' => [
        'mcp' => ['public' => true],
        'annotations' => ['readonly' => true, 'destructive' => false, 'idempotent' => true],
    ],
]);

/** @param array<string, mixed> $input */
function contact_form_7_get_form(array $input): array|WP_Error
{
    $ready = require_contact_form_7();
    if ($ready instanceof WP_Error) {
        return $ready;
    }

    $form_id = isset($input['form_id']) && is_scalar($input['form_id']) ? (int) $input['form_id'] : 0;
    $post = get_target_form($form_id);
    if ($post instanceof WP_Error) {
        return $post;
    }

    return summarize_form($post);
}
