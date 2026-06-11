<?php
/**
 * Bundled LayrShift skills.
 *
 * @package LayrShift
 */

declare( strict_types=1 );

namespace LayrShift\Skills;

/**
 * Loads SKILL.md files from includes/skills/built-in/.
 */
final class BuiltIn {

	public const SOURCE_ID = 'built-in';

	public const SOURCE_PRIORITY = 10;

	/**
	 * @param array<string, array{id: string, priority: int, label: string, loader: callable}> $sources Registered sources.
	 * @return array<string, array{id: string, priority: int, label: string, loader: callable}>
	 */
	public static function register_source( array $sources ): array {
		$sources[ self::SOURCE_ID ] = array(
			'id'       => self::SOURCE_ID,
			'priority' => self::SOURCE_PRIORITY,
			'label'    => __( 'Built-in', 'layrshift' ),
			'loader'   => array( self::class, 'load' ),
		);

		return $sources;
	}

	/**
	 * @return list<array{slug: string, name: string, description: string, content: string, enable_prompt: bool, enable_agentic: bool}>
	 */
	public static function load(): array {
		static $cached = null;
		if ( is_array( $cached ) ) {
			return $cached;
		}

		$result = array();
		$dir    = __DIR__ . '/built-in';
		$files  = is_dir( $dir ) ? glob( $dir . '/*.md' ) : false;

		if ( is_array( $files ) ) {
			sort( $files );
			foreach ( $files as $path ) {
				$slug = Parser::normalize_slug( basename( $path, '.md' ) );
				if ( '' === $slug ) {
					continue;
				}

				$raw = file_get_contents( $path );
				if ( false === $raw ) {
					continue;
				}

				$parsed = Parser::parse( $raw );
				if ( null !== $parsed['parse_error'] ) {
					continue;
				}
				if ( '' === trim( $parsed['body'] ) ) {
					continue;
				}

				$result[] = array(
					'slug'            => $slug,
					'name'            => '' !== $parsed['name'] ? $parsed['name'] : $slug,
					'description'     => $parsed['description'],
					'content'         => $parsed['body'],
					'enable_prompt'   => $parsed['enable_prompt'],
					'enable_agentic'  => $parsed['enable_agentic'],
				);
			}
		}

		$cached = $result;
		return $result;
	}
}
