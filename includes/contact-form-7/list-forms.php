<?php

declare(strict_types=1);

namespace LayrShift\ContactForm7;

use WP_Error;

if (!defined('ABSPATH')) {
    exit();
}

wp_register_ability('layrshift/contact-form-7-list-forms', [
    'label' => __('List Contact Form 7 Forms', 'layrshift'),
    'description' => __('List Contact Form 7 forms with IDs, titles, and shortcodes.', 'layrshift'),
    'category' => 'layrshift-contact-form-7',
    'input_schema' => [
        'type' => 'object',
        'properties' => [
            'per_page' => ['type' => 'integer', 'default' => 50, 'minimum' => 1, 'maximum' => 100],
            'page' => ['type' => 'integer', 'default' => 1, 'minimum' => 1],
        ],
        'additionalProperties' => false,
    ],
    'execute_callback' => __NAMESPACE__ . '\\contact_form_7_list_forms',
    'permission_callback' => array(\LayrShift\Auth::class, 'check_ability_permission'),
    'meta' => [
        'mcp' => ['public' => true],
        'annotations' => ['readonly' => true, 'destructive' => false, 'idempotent' => true],
    ],
]);

/** @param array<string, mixed> $input */
function contact_form_7_list_forms(array $input): array|WP_Error
{
    $ready = require_contact_form_7();
    if ($ready instanceof WP_Error) {
        return $ready;
    }

    $per_page = isset($input['per_page']) && is_numeric($input['per_page']) ? max(1, min(100, (int) $input['per_page'])) : 50;
    $page     = isset($input['page']) && is_numeric($input['page']) ? max(1, (int) $input['page']) : 1;

    $query = new \WP_Query(
        array(
            'post_type'      => 'wpcf7_contact_form',
            'post_status'    => 'publish',
            'posts_per_page' => $per_page,
            'paged'          => $page,
            'orderby'        => 'title',
            'order'          => 'ASC',
        )
    );

    $forms = array();
    foreach ($query->posts as $post) {
        $forms[] = array(
            'id' => $post->ID,
            'title' => $post->post_title,
            'slug' => $post->post_name,
            'shortcode' => '[contact-form-7 id="' . $post->ID . '" title="' . esc_attr($post->post_title) . '"]',
        );
    }

    return array(
        'page' => $page,
        'per_page' => $per_page,
        'total' => (int) $query->found_posts,
        'total_pages' => (int) $query->max_num_pages,
        'forms' => $forms,
    );
}
