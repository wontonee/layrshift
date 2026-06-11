<?php
/**
 * Skill catalog injection for discover-abilities.
 *
 * @package LayrShift
 */

declare( strict_types=1 );

namespace LayrShift\Skills;

/**
 * Prepends the skill catalog to LayrShift MCP instructions.
 */
final class Catalog {

	/**
	 * @param mixed $instructions Base instructions.
	 * @return mixed
	 */
	public static function inject( mixed $instructions ): mixed {
		if ( ! is_string( $instructions ) ) {
			return $instructions;
		}

		$skills = Sources::discoverable( 'agentic' );
		if ( array() === $skills ) {
			return $instructions;
		}

		return self::render( $skills ) . "\n" . $instructions;
	}

	/**
	 * @param list<array<string, mixed>> $skills Discoverable skills.
	 */
	public static function render( array $skills ): string {
		$lines = array(
			'',
			'## Available Skills',
			'',
			'When a skill description matches the user\'s request, call `layrshift/skill-get` with the slug to load its full instructions before starting work.',
			'',
		);

		foreach ( $skills as $skill ) {
			$lines[] = sprintf(
				'- **`%s`** *(%s)* — %s',
				(string) ( $skill['slug'] ?? '' ),
				(string) ( $skill['source_label'] ?? '' ),
				trim( (string) ( $skill['description'] ?? '' ) )
			);
		}

		$lines[] = '';
		return implode( "\n", $lines );
	}
}
