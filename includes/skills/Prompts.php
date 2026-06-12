<?php
/**
 * Dynamic MCP prompt abilities for discoverable skills.
 *
 * @package LayrShift
 */

declare( strict_types=1 );

namespace LayrShift\Skills;

use LayrShift\AbilityCategories;
use LayrShift\Auth;

/**
 * Registers layrshift/skill-prompt-{slug} abilities for MCP prompts/list.
 */
final class Prompts {

	public static function register_dynamic_abilities(): void {
		if ( ! function_exists( 'wp_register_ability' ) ) {
			return;
		}

		foreach ( Sources::discoverable( 'prompt' ) as $skill ) {
			$slug = (string) ( $skill['slug'] ?? '' );
			if ( '' === $slug ) {
				continue;
			}

			$name        = (string) ( $skill['name'] ?? $slug );
			$description = (string) ( $skill['description'] ?? '' );
			$body        = Parser::render_skill_md(
				array(
					'slug'            => $slug,
					'description'     => $description,
					'content'         => (string) ( $skill['content'] ?? '' ),
					'enable_prompt'   => (bool) ( $skill['enable_prompt'] ?? true ),
					'enable_agentic'  => (bool) ( $skill['enable_agentic'] ?? true ),
				)
			);

			$ability_name = 'layrshift/skill-prompt-' . $slug;
			if ( wp_has_ability( $ability_name ) ) {
				continue;
			}

			wp_register_ability(
				$ability_name,
				array(
					'label'               => $name,
					'description'         => $description,
					'category'            => AbilityCategories::SKILL,
					'input_schema'        => array(
						'type'       => 'object',
						'properties' => new \stdClass(),
					),
					'output_schema'       => array(
						'type'       => 'object',
						'properties' => array(
							'messages' => array( 'type' => 'array' ),
						),
					),
					'execute_callback'    => static fn(): array => array(
						'messages' => array(
							array(
								'role'    => 'user',
								'content' => array(
									'type' => 'text',
									'text' => $body,
								),
							),
						),
					),
					'permission_callback' => array( Auth::class, 'check_ability_permission' ),
					'meta'                => array(
						'mcp' => array(
							'public' => true,
							'type'   => 'prompt',
						),
					),
				)
			);
		}
	}

	/**
	 * @return list<string>
	 */
	public static function ability_names(): array {
		$names = array();
		foreach ( Sources::discoverable( 'prompt' ) as $skill ) {
			$slug = (string) ( $skill['slug'] ?? '' );
			if ( '' !== $slug ) {
				$names[] = 'layrshift/skill-prompt-' . $slug;
			}
		}
		return $names;
	}
}
