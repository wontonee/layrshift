<?php

declare(strict_types=1);

namespace LayrShift\PrismShift;

use PrismShift\Analysis\PageHealthAnalyzer;
use PrismShift\Meta\PostMeta;
use WP_Error;
use WP_Post;

if (!defined('ABSPATH')) {
    exit();
}

function is_prismshift_available(): bool
{
    return defined('PRISMSHIFT_VERSION');
}

/** @return true|WP_Error */
function require_prismshift(): true|WP_Error
{
    if (!is_prismshift_available()) {
        return new WP_Error('prismshift_not_active', __('PrismShift is not active on this site.', 'layrshift'));
    }

    return true;
}

/** @param array<string, mixed> $input */
function input_post_id(array $input): int
{
    if (array_key_exists('post_id', $input)) {
        return is_scalar($input['post_id']) ? (int) $input['post_id'] : 0;
    }

    if (array_key_exists('target_id', $input)) {
        return is_scalar($input['target_id']) ? (int) $input['target_id'] : 0;
    }

    return 0;
}

function get_target_post(int $post_id): WP_Post|WP_Error
{
    if ($post_id <= 0) {
        return new WP_Error('prismshift_invalid_post_id', __('A valid post_id is required.', 'layrshift'));
    }

    $post = get_post($post_id);
    if (!$post instanceof WP_Post) {
        return new WP_Error('prismshift_post_not_found', sprintf(
            /* translators: %d: post ID */
            __('Post %d was not found.', 'layrshift'),
            $post_id
        ));
    }

    return $post;
}

/**
 * @return array<string, mixed>
 */
function read_post_seo(int $post_id): array
{
    $meta = PostMeta::get($post_id);

    return array(
        'post_id' => $post_id,
        'raw' => array(
            'seo_title' => (string) ($meta['title'] ?? ''),
            'meta_description' => (string) ($meta['description'] ?? ''),
            'focus_keyword' => (string) ($meta['focus_keyword'] ?? ''),
            'noindex' => !empty($meta['noindex']) ? '1' : '',
            'canonical' => (string) ($meta['canonical'] ?? ''),
            'schema_type' => (string) ($meta['schema_type'] ?? ''),
        ),
    );
}

/**
 * @param array<string, mixed> $fields
 */
function write_post_seo(int $post_id, array $fields): true|WP_Error
{
    $payload = array();

    if (array_key_exists('seo_title', $fields) && is_scalar($fields['seo_title'])) {
        $payload['title'] = (string) $fields['seo_title'];
    }
    if (array_key_exists('meta_description', $fields) && is_scalar($fields['meta_description'])) {
        $payload['description'] = (string) $fields['meta_description'];
    }
    if (array_key_exists('focus_keyword', $fields) && is_scalar($fields['focus_keyword'])) {
        $payload['focus_keyword'] = (string) $fields['focus_keyword'];
    }
    if (array_key_exists('noindex', $fields)) {
        $payload['noindex'] = ( '1' === (string) $fields['noindex'] || true === $fields['noindex'] );
    }
    if (array_key_exists('canonical', $fields) && is_scalar($fields['canonical'])) {
        $payload['canonical'] = (string) $fields['canonical'];
    }
    if (array_key_exists('schema_type', $fields) && is_scalar($fields['schema_type'])) {
        $payload['schema_type'] = (string) $fields['schema_type'];
    }

    if (empty($payload)) {
        return new WP_Error('prismshift_no_fields', __('No PrismShift SEO fields were provided to update.', 'layrshift'));
    }

    PostMeta::update($post_id, $payload);

    return true;
}

/**
 * @return array<string, mixed>
 */
function analyze_post_seo(int $post_id): array
{
    $post = get_post($post_id);
    if (!$post instanceof WP_Post) {
        return array('post_id' => $post_id, 'issues' => array());
    }

    $report = PageHealthAnalyzer::analyze($post_id);
    $report['post_id'] = $post_id;

    return $report;
}
