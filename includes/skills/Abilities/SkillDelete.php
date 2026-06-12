<?php
/**
 * layrshift/skill-delete ability.
 *
 * @package LayrShift
 */

declare( strict_types=1 );

namespace LayrShift\Skills\Abilities;

use LayrShift\AbilityCategories;
use LayrShift\Auth;
use WP_Error;

/**
 * Deletes or trashes user skills.
 */
final class SkillDelete {

	public static function register(): void {
		if ( ! function_exists( 'wp_register_ability' ) ) {
			return;
		}

		wp_register_ability(
			'layrshift/skill-delete',
			array(
				'label'               => __( 'Delete Skill', 'layrshift' ),
				'description'         => __( 'Move a LayrShift user skill to trash. Pass permanent=true to delete immediately.', 'layrshift' ),
				'category'            => AbilityCategories::SKILL,
				'execute_callback'    => array( self::class, 'execute' ),
				'permission_callback' => array( Auth::class, 'check_ability_permission' ),
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'slug'      => array( 'type' => 'string' ),
						'permanent' => array( 'type' => 'boolean' ),
					),
					'required'   => array( 'slug' ),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success' => array( 'type' => 'boolean' ),
						'deleted' => array( 'type' => 'boolean' ),
						'trashed' => array( 'type' => 'boolean' ),
						'reason'  => array( 'type' => 'string' ),
					),
					'required'   => array( 'success' ),
				),
				'meta'                => array(
					'annotations' => array(
						'readOnly'    => false,
						'destructive' => true,
						'idempotent'  => true,
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
			return array(
				'success' => true,
				'deleted' => false,
				'trashed' => false,
				'reason'  => 'not_user_managed_or_not_found',
			);
		}

		$permanent = filter_var( $input['permanent'] ?? false, FILTER_VALIDATE_BOOLEAN );
		if ( $permanent ) {
			$result = wp_delete_post( $post->ID, true );
			return array(
				'success' => (bool) $result,
				'deleted' => (bool) $result,
				'trashed' => false,
			);
		}

		$result = wp_trash_post( $post->ID );
		return array(
			'success' => false !== $result && null !== $result,
			'deleted' => false,
			'trashed' => false !== $result && null !== $result,
		);
	}
}
