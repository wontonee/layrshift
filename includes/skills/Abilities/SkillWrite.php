<?php
/**
 * layrshift/skill-write ability.
 *
 * @package LayrShift
 */

declare( strict_types=1 );

namespace LayrShift\Skills\Abilities;

use LayrShift\Auth;
use LayrShift\Skills\Cpt;
use LayrShift\Skills\Parser;
use LayrShift\Skills\Sources;
use WP_Error;

/**
 * Creates or updates user skills.
 */
final class SkillWrite {

	public static function register(): void {
		if ( ! function_exists( 'wp_register_ability' ) ) {
			return;
		}

		wp_register_ability(
			'layrshift/skill-write',
			array(
				'label'               => __( 'Write Skill', 'layrshift' ),
				'description'         => __( 'Create or update a LayrShift user skill. The title becomes the slug.', 'layrshift' ),
				'category'            => 'skill',
				'execute_callback'    => array( self::class, 'execute' ),
				'permission_callback' => array( Auth::class, 'check_ability_permission' ),
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'title'           => array( 'type' => 'string' ),
						'description'     => array( 'type' => 'string' ),
						'content'         => array( 'type' => 'string' ),
						'enable_prompt'   => array( 'type' => 'boolean' ),
						'enable_agentic'  => array( 'type' => 'boolean' ),
						'on_conflict'     => array(
							'type' => 'string',
							'enum' => array( 'fail', 'replace', 'rename' ),
						),
					),
					'required'   => array( 'title', 'description', 'content' ),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success' => array( 'type' => 'boolean' ),
						'slug'    => array( 'type' => 'string' ),
						'action'  => array(
							'type' => 'string',
							'enum' => array( 'created', 'updated', 'renamed' ),
						),
					),
					'required'   => array( 'success' ),
				),
				'meta'                => array(
					'annotations' => array(
						'readOnly'    => false,
						'destructive' => true,
						'idempotent'  => false,
					),
					'mcp'         => array(
						'public' => true,
						'type'   => 'tool',
					),
				),
			)
		);
	}

	/**
	 * @param array<string, mixed> $input Ability input.
	 * @return array<string, mixed>|WP_Error
	 */
	public static function execute( array $input ): array|WP_Error {
		$title = sanitize_title( (string) ( $input['title'] ?? '' ) );
		if ( '' === $title ) {
			return new WP_Error(
				'invalid_title',
				__( 'Title is required and must contain at least one letter or digit.', 'layrshift' )
			);
		}

		$description = trim( (string) ( $input['description'] ?? '' ) );
		$content     = Parser::unescape_content( (string) ( $input['content'] ?? '' ) );

		if ( strlen( $content ) > Parser::MAX_BODY_BYTES ) {
			return new WP_Error( 'body_too_large', __( 'Body exceeds 1 MB.', 'layrshift' ) );
		}

		$external_label = Sources::exists_in_external_source( $title );
		if ( null !== $external_label ) {
			return new WP_Error(
				'slug_in_external_source',
				sprintf(
					/* translators: 1: slug, 2: source label */
					__( 'Slug "%1$s" is already used by source "%2$s". Choose a different title.', 'layrshift' ),
					$title,
					$external_label
				),
				array(
					'slug'   => $title,
					'source' => $external_label,
				)
			);
		}

		$on_conflict = (string) ( $input['on_conflict'] ?? 'fail' );
		if ( ! in_array( $on_conflict, array( 'fail', 'replace', 'rename' ), true ) ) {
			$on_conflict = 'fail';
		}

		$existing = self::find_user_post_by_slug( $title );
		$action   = 'created';
		$slug     = $title;

		if ( null !== $existing ) {
			if ( 'fail' === $on_conflict ) {
				return new WP_Error(
					'slug_exists',
					__( 'A skill with this title already exists.', 'layrshift' ),
					array(
						'slug'           => $slug,
						'suggested_slug' => self::find_free_suffix( $slug ),
					)
				);
			}
			if ( 'rename' === $on_conflict ) {
				$slug     = self::find_free_suffix( $slug );
				$action   = 'renamed';
				$existing = null;
			}
			if ( null !== $existing ) {
				$action = 'updated';
			}
		}

		$enable_prompt  = filter_var( $input['enable_prompt'] ?? true, FILTER_VALIDATE_BOOLEAN );
		$enable_agentic = filter_var( $input['enable_agentic'] ?? true, FILTER_VALIDATE_BOOLEAN );

		$postarr = array(
			'post_type'    => Cpt::POST_TYPE,
			'post_status'  => 'publish',
			'post_title'   => $slug,
			'post_name'    => $slug,
			'post_excerpt' => $description,
			'post_content' => $content,
		);

		if ( null !== $existing ) {
			$postarr['ID'] = $existing->ID;
			$post_id       = wp_update_post( wp_slash( $postarr ), true );
		} else {
			$post_id = wp_insert_post( wp_slash( $postarr ), true );
		}

		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		update_post_meta( $post_id, Cpt::META_ENABLE_PROMPT, $enable_prompt );
		update_post_meta( $post_id, Cpt::META_ENABLE_AGENTIC, $enable_agentic );

		return array(
			'success' => true,
			'slug'    => $slug,
			'action'  => $action,
		);
	}

	public static function find_user_post_by_slug( string $slug ): ?\WP_Post {
		/** @var list<\WP_Post> $posts */
		$posts = get_posts(
			array(
				'post_type'      => Cpt::POST_TYPE,
				'post_status'    => array( 'publish', 'draft' ),
				'name'           => $slug,
				'posts_per_page' => 1,
				'no_found_rows'  => true,
			)
		);

		return $posts[0] ?? null;
	}

	public static function find_free_suffix( string $slug ): string {
		$i = 2;
		while (
			null !== self::find_user_post_by_slug( $slug . '-' . $i )
			|| null !== Sources::exists_in_external_source( $slug . '-' . $i )
		) {
			++$i;
			if ( $i > 9999 ) {
				return $slug . '-' . time();
			}
		}

		return $slug . '-' . $i;
	}
}
