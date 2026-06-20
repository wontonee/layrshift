<?php

declare(strict_types=1);

namespace LayrShift\PrismShift;

use PrismShift\Core\Settings;
use WP_Error;

if (!defined('ABSPATH')) {
    exit();
}

wp_register_ability('layrshift/prismshift-ai-optimize-post', [
    'label' => __('AI Optimize PrismShift Post SEO', 'layrshift'),
    'description' => __('Suggest PrismShift SEO field updates using configured AI provider (draft-only by default).', 'layrshift'),
    'category' => 'layrshift-prismshift',
    'input_schema' => [
        'type' => 'object',
        'properties' => [
            'post_id' => ['type' => 'integer'],
            'target_id' => ['type' => 'integer'],
            'apply' => ['type' => 'boolean', 'default' => false, 'description' => 'When true, write suggested fields to post meta.'],
            'allow_publish' => ['type' => 'boolean', 'default' => false],
        ],
        'anyOf' => [
            ['required' => ['post_id']],
            ['required' => ['target_id']],
        ],
        'additionalProperties' => false,
    ],
    'execute_callback' => __NAMESPACE__ . '\\prismshift_ai_optimize_post',
    'permission_callback' => array(\LayrShift\Auth::class, 'check_ability_permission'),
    'meta' => [
        'mcp' => ['public' => true],
        'annotations' => ['destructive' => true],
    ],
]);

/** @param array<string, mixed> $input */
function prismshift_ai_optimize_post(array $input): array|WP_Error
{
    $ready = require_prismshift();
    if ($ready instanceof WP_Error) {
        return $ready;
    }

    $settings = Settings::get();
    if (!Settings::is_setup_complete($settings)) {
        return new WP_Error(
            'prismshift_ai_not_configured',
            __('PrismShift AI is not configured. Complete AI Setup in wp-admin first.', 'layrshift')
        );
    }

    $post_id = input_post_id($input);
    $post = get_target_post($post_id);
    if ($post instanceof WP_Error) {
        return $post;
    }

    $allow_publish = !empty($input['allow_publish']);
    if ($post->post_status === 'publish' && !empty($input['apply']) && !$allow_publish) {
        return new WP_Error(
            'prismshift_publish_not_allowed',
            __('Applying AI SEO on published posts requires allow_publish=true and explicit user approval.', 'layrshift')
        );
    }

    $analysis = analyze_post_seo($post_id);
    $current = read_post_seo($post_id);
    $raw = is_array($current['raw'] ?? null) ? $current['raw'] : array();
    $suggestions = array(
        'seo_title' => (string) ($raw['seo_title'] ?? $post->post_title),
        'meta_description' => (string) ($raw['meta_description'] ?? ''),
        'focus_keyword' => (string) ($raw['focus_keyword'] ?? ''),
        'notes' => __('Full AI generation ships in a future PrismShift release; this ability returns heuristic suggestions based on current content.', 'layrshift'),
    );

    if (empty(trim($suggestions['meta_description']))) {
        $excerpt = wp_trim_words(wp_strip_all_tags((string) $post->post_content), 25, '…');
        $suggestions['meta_description'] = $excerpt;
    }

    $response = array(
        'post_id' => $post_id,
        'suggestions' => $suggestions,
        'analysis' => $analysis,
        'applied' => false,
    );

    if (!empty($input['apply'])) {
        $written = write_post_seo($post_id, $suggestions);
        if ($written instanceof WP_Error) {
            return $written;
        }
        $response['applied'] = true;
        $response['post_seo'] = read_post_seo($post_id);
    }

    return $response;
}
