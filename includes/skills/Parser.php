<?php
/**
 * SKILL.md parser for LayrShift skills.
 *
 * @package LayrShift
 */

declare( strict_types=1 );

namespace LayrShift\Skills;

/**
 * Parses and renders SKILL.md documents.
 */
final class Parser {

	public const MAX_BODY_BYTES = 1048576;

	/**
	 * @param string $raw Raw SKILL.md content.
	 * @return array{name: string, description: string, enable_prompt: bool, enable_agentic: bool, body: string, parse_error: ?string}
	 */
	public static function parse( string $raw ): array {
		$name            = '';
		$description     = '';
		$enable_prompt   = true;
		$enable_agentic  = true;
		$body            = $raw;
		$parse_error     = null;

		$normalized = preg_replace( '/\r\n?/', "\n", $raw );
		if ( ! is_string( $normalized ) ) {
			$normalized = $raw;
		}

		if ( str_starts_with( $normalized, "---\n" ) ) {
			$closing = strpos( $normalized, "\n---\n", 4 );
			if ( false === $closing && str_ends_with( $normalized, "\n---" ) ) {
				$closing = strlen( $normalized ) - 4;
			}

			if ( false !== $closing ) {
				$frontmatter_raw = substr( $normalized, 4, $closing - 4 );
				$body            = ltrim( substr( $normalized, $closing + 5 ), "\n" );

				foreach ( explode( "\n", $frontmatter_raw ) as $line ) {
					$trimmed = trim( $line );
					if ( '' === $trimmed || str_starts_with( $trimmed, '#' ) ) {
						continue;
					}

					$colon = strpos( $line, ':' );
					if ( false === $colon ) {
						continue;
					}

					$key   = strtolower( trim( substr( $line, 0, $colon ) ) );
					$value = trim( substr( $line, $colon + 1 ) );
					if ( str_starts_with( $value, '"' ) && str_ends_with( $value, '"' ) ) {
						$value = substr( $value, 1, -1 );
					}
					if ( str_starts_with( $value, "'" ) && str_ends_with( $value, "'" ) ) {
						$value = substr( $value, 1, -1 );
					}

					switch ( $key ) {
						case 'name':
							$name = $value;
							break;
						case 'description':
							$description = $value;
							break;
						case 'enable_prompt':
							$enable_prompt = filter_var( $value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE ) ?? true;
							break;
						case 'enable_agentic':
							$enable_agentic = filter_var( $value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE ) ?? true;
							break;
					}
				}
			} else {
				$parse_error = __( 'Frontmatter started with --- but had no closing ---', 'layrshift' );
			}
		}

		return array(
			'name'            => $name,
			'description'     => $description,
			'enable_prompt'   => $enable_prompt,
			'enable_agentic'  => $enable_agentic,
			'body'            => $body,
			'parse_error'     => $parse_error,
		);
	}

	public static function unescape_content( string $raw ): string {
		return stripcslashes( $raw );
	}

	public static function normalize_slug( string $raw ): string {
		$candidate = sanitize_title( $raw );
		if ( '' === $candidate ) {
			return '';
		}

		if ( strlen( $candidate ) > 60 ) {
			$candidate = rtrim( substr( $candidate, 0, 60 ), '-' );
		}

		return $candidate;
	}

	/**
	 * @param array{slug?: string, description?: string, enable_prompt?: bool, enable_agentic?: bool, content?: string} $skill Skill record.
	 */
	public static function render_skill_md( array $skill ): string {
		return sprintf(
			"---\nname: %s\ndescription: %s\nenable_prompt: %s\nenable_agentic: %s\n---\n\n%s",
			$skill['slug'] ?? '',
			str_replace( "\n", ' ', $skill['description'] ?? '' ),
			! empty( $skill['enable_prompt'] ) ? 'true' : 'false',
			! empty( $skill['enable_agentic'] ) ? 'true' : 'false',
			$skill['content'] ?? ''
		);
	}
}
