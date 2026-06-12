<?php
/**
 * layrshift/skill-get ability.
 *
 * @package LayrShift
 */

declare( strict_types=1 );

namespace LayrShift\Skills\Abilities;

use LayrShift\AbilityCategories;
use LayrShift\Auth;
use LayrShift\Skills\Parser;
use LayrShift\Skills\Sources;
use WP_Error;

/**
 * Loads a skill by slug.
 */
final class SkillGet {

	public static function register_category(): void {
		if ( ! function_exists( 'wp_register_ability_category' ) ) {
			return;
		}

		if ( wp_has_ability_category( 'skill' ) ) {
			return;
		}

		wp_register_ability_category(
			'skill',
			array(
				'label'       => __( 'Skills', 'layrshift' ),
				'description' => __( 'Manage and load LayrShift agent skills.', 'layrshift' ),
			)
		);
	}

	public static function register(): void {
		if ( ! function_exists( 'wp_register_ability' ) ) {
			return;
		}

		wp_register_ability(
			'layrshift/skill-get',
			array(
				'label'               => __( 'Get Skill', 'layrshift' ),
				'description'         => __( 'Load a LayrShift skill by slug. Returns the full SKILL.md content plus metadata.', 'layrshift' ),
				'category'            => AbilityCategories::SKILL,
				'execute_callback'    => array( self::class, 'execute' ),
				'permission_callback' => array( Auth::class, 'check_ability_permission' ),
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'slug' => array(
							'type'        => 'string',
							'description' => __( 'The slug of the skill to load.', 'layrshift' ),
						),
					),
					'required'   => array( 'slug' ),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'found'           => array( 'type' => 'boolean' ),
						'slug'            => array( 'type' => 'string' ),
						'name'            => array( 'type' => 'string' ),
						'description'     => array( 'type' => 'string' ),
						'content'         => array( 'type' => 'string' ),
						'enable_prompt'   => array( 'type' => 'boolean' ),
						'enable_agentic'  => array( 'type' => 'boolean' ),
						'source'          => array( 'type' => 'string' ),
					),
					'required'   => array( 'found' ),
				),
				'meta'                => array(
					'annotations' => array(
						'readOnly'    => true,
						'destructive' => false,
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
		$slug = self::normalize_requested_slug( (string) ( $input['slug'] ?? '' ) );
		if ( '' === $slug ) {
			return new WP_Error( 'missing_slug', __( 'A slug is required.', 'layrshift' ) );
		}

		$skill = Sources::find( $slug );
		if ( null === $skill ) {
			return array( 'found' => false );
		}

		$enable_prompt  = (bool) ( $skill['enable_prompt'] ?? false );
		$enable_agentic = (bool) ( $skill['enable_agentic'] ?? true );

		return array(
			'found'           => true,
			'slug'            => (string) $skill['slug'],
			'name'            => (string) ( $skill['name'] ?? $skill['slug'] ),
			'description'     => (string) ( $skill['description'] ?? '' ),
			'content'         => Parser::render_skill_md(
				array(
					'slug'            => (string) $skill['slug'],
					'description'     => (string) ( $skill['description'] ?? '' ),
					'content'         => (string) ( $skill['content'] ?? '' ),
					'enable_prompt'   => $enable_prompt,
					'enable_agentic'  => $enable_agentic,
				)
			),
			'enable_prompt'   => $enable_prompt,
			'enable_agentic'  => $enable_agentic,
			'source'          => (string) ( $skill['source'] ?? 'user-cpt' ),
		);
	}

	private static function normalize_requested_slug( string $slug ): string {
		$normalized = trim( $slug );
		if ( str_starts_with( $normalized, 'layrshift/' ) ) {
			return substr( $normalized, strlen( 'layrshift/' ) );
		}

		return $normalized;
	}
}
