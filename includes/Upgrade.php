<?php
/**
 * One-time data migrations between LayrShift versions.
 *
 * @package LayrShift
 */

declare( strict_types=1 );

namespace LayrShift;

use LayrShift\Skills\Cpt;

/**
 * Runs idempotent upgrade routines.
 */
final class Upgrade {

	private const VERSION_OPTION = 'layrshift_db_version';

	public static function init(): void {
		add_action( 'plugins_loaded', array( self::class, 'maybe_run' ), 5 );
	}

	public static function maybe_run(): void {
		$stored = get_option( self::VERSION_OPTION, '0' );
		if ( version_compare( (string) $stored, LAYRSHIFT_VERSION, '>=' ) ) {
			return;
		}

		self::migrate_skill_meta();
		update_option( self::VERSION_OPTION, LAYRSHIFT_VERSION, false );
	}

	private static function migrate_skill_meta(): void {
		$legacy_prompt  = '_enable_prompt';
		$legacy_agentic = '_enable_agentic';

		$posts = get_posts(
			array(
				'post_type'      => Cpt::POST_TYPE,
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'fields'         => 'ids',
			)
		);

		foreach ( $posts as $post_id ) {
			$post_id = (int) $post_id;

			if ( metadata_exists( 'post', $post_id, $legacy_prompt ) && ! metadata_exists( 'post', $post_id, Cpt::META_ENABLE_PROMPT ) ) {
				update_post_meta( $post_id, Cpt::META_ENABLE_PROMPT, get_post_meta( $post_id, $legacy_prompt, true ) );
				delete_post_meta( $post_id, $legacy_prompt );
			}

			if ( metadata_exists( 'post', $post_id, $legacy_agentic ) && ! metadata_exists( 'post', $post_id, Cpt::META_ENABLE_AGENTIC ) ) {
				update_post_meta( $post_id, Cpt::META_ENABLE_AGENTIC, get_post_meta( $post_id, $legacy_agentic, true ) );
				delete_post_meta( $post_id, $legacy_agentic );
			}
		}
	}
}
