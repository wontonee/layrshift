<?php
/**
 * Skills custom post type.
 *
 * @package LayrShift
 */

declare( strict_types=1 );

namespace LayrShift\Skills;

/**
 * Registers the layrshift_skill post type.
 */
final class Cpt {

	public const POST_TYPE = 'layrshift_skill';

	public const META_ENABLE_PROMPT = '_enable_prompt';

	public const META_ENABLE_AGENTIC = '_enable_agentic';

	public static function register(): void {
		register_post_type(
			self::POST_TYPE,
			array(
				'label'          => __( 'Skills', 'layrshift' ),
				'public'         => false,
				'show_ui'        => false,
				'show_in_rest'   => false,
				'has_archive'    => false,
				'rewrite'        => false,
				'capability_type' => 'post',
				'map_meta_cap'   => true,
				'supports'       => array( 'title', 'editor', 'excerpt', 'revisions' ),
			)
		);

		add_filter(
			'wp_revisions_to_keep',
			static function ( int $num, \WP_Post $post ): int {
				return self::POST_TYPE === $post->post_type ? 10 : $num;
			},
			10,
			2
		);

		$auth = static fn(): bool => current_user_can( 'manage_options' );

		register_post_meta(
			self::POST_TYPE,
			self::META_ENABLE_PROMPT,
			array(
				'type'              => 'boolean',
				'single'            => true,
				'default'           => true,
				'show_in_rest'      => false,
				'auth_callback'     => $auth,
			)
		);

		register_post_meta(
			self::POST_TYPE,
			self::META_ENABLE_AGENTIC,
			array(
				'type'              => 'boolean',
				'single'            => true,
				'default'           => true,
				'show_in_rest'      => false,
				'auth_callback'     => $auth,
			)
		);
	}
}
