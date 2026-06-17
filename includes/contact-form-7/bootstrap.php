<?php

declare(strict_types=1);

namespace LayrShift\ContactForm7;

use WP_Error;
use WP_Post;

if (!defined('ABSPATH')) {
    exit();
}

function is_contact_form_7_available(): bool
{
    return defined('WPCF7_VERSION') || class_exists('WPCF7_ContactForm');
}

/** @return true|WP_Error */
function require_contact_form_7(): true|WP_Error
{
    if (!is_contact_form_7_available()) {
        return new WP_Error('contact_form_7_not_active', __('Contact Form 7 is not active on this site.', 'layrshift'));
    }

    return true;
}

/**
 * @return array<string, mixed>
 */
function collect_status(): array
{
    $counts = wp_count_posts('wpcf7_contact_form');

    return array(
        'version' => defined('WPCF7_VERSION') ? (string) WPCF7_VERSION : '',
        'form_count' => isset($counts->publish) ? (int) $counts->publish : 0,
    );
}

function get_target_form(int $form_id): WP_Post|WP_Error
{
    if ($form_id <= 0) {
        return new WP_Error('contact_form_7_invalid_form_id', __('A valid form_id is required.', 'layrshift'));
    }

    $post = get_post($form_id);
    if (!$post instanceof WP_Post || $post->post_type !== 'wpcf7_contact_form') {
        return new WP_Error('contact_form_7_form_not_found', sprintf(
            /* translators: %d: form ID */
            __('Contact Form 7 form %d was not found.', 'layrshift'),
            $form_id
        ));
    }

    return $post;
}

/**
 * @return array<string, mixed>
 */
function summarize_form(WP_Post $post): array
{
    $properties = array();
    if (class_exists('WPCF7_ContactForm')) {
        $contact_form = \WPCF7_ContactForm::get_instance($post->ID);
        if ($contact_form) {
            $props = $contact_form->get_properties();
            if (is_array($props)) {
                $mail = $props['mail'] ?? array();
                if (is_array($mail)) {
                    $properties['mail'] = array(
                        'subject' => (string) ($mail['subject'] ?? ''),
                        'recipient' => (string) ($mail['recipient'] ?? ''),
                        'sender' => (string) ($mail['sender'] ?? ''),
                    );
                }
                $properties['form'] = (string) ($props['form'] ?? $post->post_content);
            }
        }
    }

    if ($properties === array()) {
        $properties['form'] = $post->post_content;
    }

    return array(
        'id' => $post->ID,
        'title' => $post->post_title,
        'slug' => $post->post_name,
        'shortcode' => '[contact-form-7 id="' . $post->ID . '" title="' . esc_attr($post->post_title) . '"]',
        'properties' => $properties,
    );
}
