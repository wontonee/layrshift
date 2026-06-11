<?php
/**
 * Gutenberg block markup adapter.
 *
 * @package LayrShift
 */

declare( strict_types=1 );

namespace LayrShift\Pro\Adapters;

final class GutenbergAdapter implements EditorAdapterInterface {

	public function get_slug(): string {
		return 'gutenberg';
	}

	public function is_available(): bool {
		return function_exists( 'parse_blocks' ) && function_exists( 'serialize_blocks' );
	}

	public function get_system_prompt(): string {
		return implode(
			"\n",
			array(
				'You are a WordPress Gutenberg block editor expert.',
				'Return ONLY valid WordPress block markup using HTML comments (<!-- wp:... -->).',
				'Do not wrap output in markdown code fences.',
				'Do not include explanations or prose outside block markup.',
				'Use core blocks only (paragraph, heading, columns, group, image, buttons, list, quote, spacer, separator).',
				'Prefer accessible semantic structure: one h1, logical heading order, sufficient contrast-friendly layout.',
				'If the user asks for a landing page, include hero, features, and CTA sections using groups/columns.',
			)
		);
	}

	/**
	 * @return string|\WP_Error
	 */
	public function normalize_generated_content( string $raw ) {
		$content = self::strip_markdown_fences( trim( $raw ) );
		if ( '' === $content ) {
			return new \WP_Error( 'layrshift_empty_content', __( 'Generated content was empty.', 'layrshift' ) );
		}

		if ( ! function_exists( 'parse_blocks' ) ) {
			return $content;
		}

		$blocks = parse_blocks( $content );
		if ( empty( $blocks ) ) {
			return new \WP_Error(
				'layrshift_invalid_blocks',
				__( 'Generated content is not valid Gutenberg block markup.', 'layrshift' )
			);
		}

		$has_content = false;
		foreach ( $blocks as $block ) {
			if ( ! empty( $block['blockName'] ) || ! empty( trim( (string) ( $block['innerHTML'] ?? '' ) ) ) ) {
				$has_content = true;
				break;
			}
		}

		if ( ! $has_content ) {
			return new \WP_Error(
				'layrshift_invalid_blocks',
				__( 'Generated content did not contain usable blocks.', 'layrshift' )
			);
		}

		if ( function_exists( 'serialize_blocks' ) ) {
			return serialize_blocks( $blocks );
		}

		return $content;
	}

	/**
	 * @return array{post_id: int, edit_url: string}|\WP_Error
	 */
	public function create_draft( string $title, string $content ) {
		if ( ! current_user_can( 'edit_posts' ) ) {
			return new \WP_Error( 'layrshift_forbidden', __( 'You cannot create posts.', 'layrshift' ), array( 'status' => 403 ) );
		}

		$post_id = wp_insert_post(
			array(
				'post_title'   => $title,
				'post_content' => $content,
				'post_status'  => 'draft',
				'post_type'    => 'page',
			),
			true
		);

		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		return array(
			'post_id'  => (int) $post_id,
			'edit_url' => (string) get_edit_post_link( (int) $post_id, 'raw' ),
		);
	}

	private static function strip_markdown_fences( string $text ): string {
		if ( preg_match( '/^```(?:html|xml|wordpress|gutenberg)?\s*\n(.*)\n```\s*$/s', $text, $matches ) ) {
			return trim( $matches[1] );
		}

		return $text;
	}
}
