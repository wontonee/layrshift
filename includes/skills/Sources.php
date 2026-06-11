<?php
/**
 * Skill source registry and lookup.
 *
 * @package LayrShift
 */

declare( strict_types=1 );

namespace LayrShift\Skills;

/**
 * Aggregates skill sources from user CPT and plugin contributors.
 */
final class Sources {

	public const USER_CPT_PRIORITY = 50;

	/**
	 * @return list<array{id: string, priority: int, label: string, loader: callable(): list<array<string,mixed>>}>
	 */
	public static function registry(): array {
		$default = array(
			'user-cpt' => array(
				'id'       => 'user-cpt',
				'priority' => self::USER_CPT_PRIORITY,
				'label'    => __( 'User', 'layrshift' ),
				'loader'   => array( self::class, 'load_user_cpt' ),
			),
		);

		/** @var array<string, array{id: string, priority: int, label: string, loader: callable(): list<array<string,mixed>>}> $sources */
		$sources = apply_filters( 'layrshift_skill_lookup_sources', $default );

		$list = array_values( $sources );
		usort(
			$list,
			static fn( array $a, array $b ): int => $a['priority'] <=> $b['priority']
		);

		return $list;
	}

	/**
	 * @return list<array{slug: string, name: string, description: string, content: string, enable_prompt: bool, enable_agentic: bool}>
	 */
	public static function load_user_cpt(): array {
		/** @var list<\WP_Post> $posts */
		$posts = get_posts(
			array(
				'post_type'      => Cpt::POST_TYPE,
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'orderby'        => 'title',
				'order'          => 'ASC',
				'no_found_rows'  => true,
			)
		);

		$result = array();
		foreach ( $posts as $post ) {
			$slug = $post->post_name;
			if ( '' === $slug ) {
				continue;
			}

			$result[] = array(
				'slug'            => $slug,
				'name'            => $post->post_title,
				'description'     => $post->post_excerpt,
				'content'         => $post->post_content,
				'enable_prompt'   => (bool) get_post_meta( $post->ID, Cpt::META_ENABLE_PROMPT, true ),
				'enable_agentic'  => (bool) get_post_meta( $post->ID, Cpt::META_ENABLE_AGENTIC, true ),
			);
		}

		return $result;
	}

	/**
	 * @return array<string, mixed>|null
	 */
	public static function find( string $slug ): ?array {
		foreach ( self::registry() as $entry ) {
			$loader = $entry['loader'];
			if ( ! is_callable( $loader ) ) {
				continue;
			}

			foreach ( $loader() as $skill ) {
				if ( ( $skill['slug'] ?? '' ) !== $slug ) {
					continue;
				}

				$skill['source']       = $entry['id'];
				$skill['source_label']   = $entry['label'];
				return $skill;
			}
		}

		return null;
	}

	/**
	 * @return list<array<string, mixed>>
	 */
	public static function all(): array {
		$result = array();
		foreach ( self::registry() as $entry ) {
			$loader = $entry['loader'];
			if ( ! is_callable( $loader ) ) {
				continue;
			}

			foreach ( $loader() as $skill ) {
				$skill['source']       = $entry['id'];
				$skill['source_label']   = $entry['label'];
				$result[]                = $skill;
			}
		}

		return $result;
	}

	/**
	 * @param 'agentic'|'prompt' $mode Discovery mode.
	 * @return list<array<string, mixed>>
	 */
	public static function discoverable( string $mode ): array {
		$key     = 'agentic' === $mode ? 'enable_agentic' : 'enable_prompt';
		$default = 'agentic' === $mode;
		$result  = array();

		foreach ( self::all() as $skill ) {
			if ( '' === trim( (string) ( $skill['description'] ?? '' ) ) ) {
				continue;
			}
			if ( '' === trim( (string) ( $skill['content'] ?? '' ) ) ) {
				continue;
			}
			if ( ! ( $skill[ $key ] ?? $default ) ) {
				continue;
			}

			$result[] = $skill;
		}

		return $result;
	}

	public static function exists_in_external_source( string $slug ): ?string {
		foreach ( self::registry() as $entry ) {
			if ( 'user-cpt' === $entry['id'] ) {
				continue;
			}

			$loader = $entry['loader'];
			if ( ! is_callable( $loader ) ) {
				continue;
			}

			foreach ( $loader() as $skill ) {
				if ( ( $skill['slug'] ?? '' ) === $slug ) {
					return $entry['label'];
				}
			}
		}

		return null;
	}
}
