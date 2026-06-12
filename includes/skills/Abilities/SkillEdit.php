<?php
/**
 * layrshift/skill-edit ability.
 *
 * @package LayrShift
 */

declare( strict_types=1 );

namespace LayrShift\Skills\Abilities;

use LayrShift\AbilityCategories;
use LayrShift\Auth;
use LayrShift\Skills\Cpt;
use LayrShift\Skills\Parser;
use LayrShift\Skills\Sources;
use WP_Error;

/**
 * Patches fields on existing user skills.
 */
final class SkillEdit {

	public static function register(): void {
		if ( ! function_exists( 'wp_register_ability' ) ) {
			return;
		}

		wp_register_ability(
			'layrshift/skill-edit',
			array(
				'label'               => __( 'Edit Skill', 'layrshift' ),
				'description'         => __( 'Update one or more fields on an existing user skill.', 'layrshift' ),
				'category'            => AbilityCategories::SKILL,
				'execute_callback'    => array( self::class, 'execute' ),
				'permission_callback' => array( Auth::class, 'check_ability_permission' ),
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'slug'            => array( 'type' => 'string' ),
						'title'           => array( 'type' => 'string' ),
						'description'     => array( 'type' => 'string' ),
						'content'         => array( 'type' => 'string' ),
						'enable_prompt'   => array( 'type' => 'boolean' ),
						'enable_agentic'  => array( 'type' => 'boolean' ),
						'enabled'         => array( 'type' => 'boolean' ),
					),
					'required'   => array( 'slug' ),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success'        => array( 'type' => 'boolean' ),
						'slug'           => array( 'type' => 'string' ),
						'changed_fields' => array(
							'type'  => 'array',
							'items' => array( 'type' => 'string' ),
						),
					),
					'required'   => array( 'success' ),
				),
				'meta'                => array(
					'annotations' => array(
						'readOnly'    => false,
						'destructive' => false,
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
		$slug = (string) ( $input['slug'] ?? '' );
		if ( '' === $slug ) {
			return new WP_Error( 'missing_slug', __( 'A slug is required.', 'layrshift' ) );
		}

		$post = SkillWrite::find_user_post_by_slug( $slug );
		if ( null === $post ) {
			return new WP_Error(
				'not_found',
				__( 'Skill not found. Only user-authored skills can be edited.', 'layrshift' )
			);
		}

		$changed      = array();
		$current_slug = $slug;

		if ( array_key_exists( 'title', $input ) ) {
			$new_title = sanitize_title( (string) $input['title'] );
			if ( '' === $new_title ) {
				return new WP_Error(
					'invalid_title',
					__( 'Title must contain at least one letter or digit.', 'layrshift' )
				);
			}

			if ( $new_title !== $post->post_name ) {
				$external = Sources::exists_in_external_source( $new_title );
				if ( null !== $external ) {
					return new WP_Error(
						'slug_in_external_source',
						sprintf(
							/* translators: 1: slug, 2: source label */
							__( 'Title "%1$s" is already used by source "%2$s".', 'layrshift' ),
							$new_title,
							$external
						)
					);
				}

				$clash = SkillWrite::find_user_post_by_slug( $new_title );
				if ( null !== $clash && (int) $clash->ID !== (int) $post->ID ) {
					return new WP_Error( 'slug_exists', __( 'A skill with this title already exists.', 'layrshift' ) );
				}

				wp_update_post(
					array(
						'ID'         => $post->ID,
						'post_title' => $new_title,
						'post_name'  => $new_title,
					)
				);
				$changed[]      = 'title';
				$current_slug   = $new_title;
			}
		}

		if ( array_key_exists( 'description', $input ) ) {
			$new_description = trim( (string) $input['description'] );
			if ( '' === $new_description ) {
				return new WP_Error( 'missing_description', __( 'Description cannot be empty.', 'layrshift' ) );
			}
			if ( $new_description !== $post->post_excerpt ) {
				wp_update_post(
					array(
						'ID'           => $post->ID,
						'post_excerpt' => $new_description,
					)
				);
				$changed[] = 'description';
			}
		}

		if ( array_key_exists( 'content', $input ) ) {
			$new_content = Parser::unescape_content( (string) $input['content'] );
			if ( strlen( $new_content ) > Parser::MAX_BODY_BYTES ) {
				return new WP_Error( 'body_too_large', __( 'Body exceeds 1 MB.', 'layrshift' ) );
			}
			if ( $new_content !== $post->post_content ) {
				wp_update_post(
					array(
						'ID'           => $post->ID,
						'post_content' => $new_content,
					)
				);
				$changed[] = 'content';
			}
		}

		if ( array_key_exists( 'enable_prompt', $input ) ) {
			$new     = (bool) $input['enable_prompt'];
			$current = (bool) get_post_meta( $post->ID, Cpt::META_ENABLE_PROMPT, true );
			if ( $new !== $current ) {
				update_post_meta( $post->ID, Cpt::META_ENABLE_PROMPT, $new );
				$changed[] = 'enable_prompt';
			}
		}

		if ( array_key_exists( 'enable_agentic', $input ) ) {
			$new     = (bool) $input['enable_agentic'];
			$current = (bool) get_post_meta( $post->ID, Cpt::META_ENABLE_AGENTIC, true );
			if ( $new !== $current ) {
				update_post_meta( $post->ID, Cpt::META_ENABLE_AGENTIC, $new );
				$changed[] = 'enable_agentic';
			}
		}

		if ( array_key_exists( 'enabled', $input ) ) {
			$new_status = (bool) $input['enabled'] ? 'publish' : 'draft';
			if ( $new_status !== $post->post_status ) {
				wp_update_post(
					array(
						'ID'          => $post->ID,
						'post_status' => $new_status,
					)
				);
				$changed[] = 'enabled';
			}
		}

		return array(
			'success'        => true,
			'slug'           => $current_slug,
			'changed_fields' => $changed,
		);
	}
}
