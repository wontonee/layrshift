<?php
/**
 * LayrShift-prefixed ability category slugs (avoid collisions with core/other plugins).
 *
 * @package LayrShift
 */

declare( strict_types=1 );

namespace LayrShift;

/**
 * Registered via wp_register_ability_category() in McpBootstrap.
 */
final class AbilityCategories {

	public const MCP_ADAPTER   = 'layrshift-mcp-adapter';
	public const SKILL         = 'layrshift-skill';
	public const CODE_EXECUTION = 'layrshift-code-execution';
	public const FILESYSTEM    = 'layrshift-filesystem';
	public const ADMIN_ACCESS  = 'layrshift-admin-access';
	public const GUTENBERG     = 'layrshift-gutenberg';
	public const ELEMENTOR     = 'layrshift-elementor';
	public const PRISMSHIFT     = 'layrshift-prismshift';
	public const YOAST         = 'layrshift-yoast';
	public const SMUSH         = 'layrshift-smush';
	public const VAULTSHIFT    = 'layrshift-vaultshift';
	public const BLOGIBOT      = 'layrshift-blogibot';
	public const WP_ROCKET     = 'layrshift-wp-rocket';
	public const MIGRATE_GURU  = 'layrshift-migrate-guru';
	public const LITESPEED     = 'layrshift-litespeed';
	public const WP_OPTIMIZE   = 'layrshift-wp-optimize';
	public const WP_FASTEST_CACHE = 'layrshift-wp-fastest-cache';
	public const WOOCOMMERCE      = 'layrshift-woocommerce';
	public const RANK_MATH        = 'layrshift-rank-math';
	public const GENESIS          = 'layrshift-genesis';
	public const ASTRA            = 'layrshift-astra';
	public const CONTACT_FORM_7   = 'layrshift-contact-form-7';
	public const WORDFENCE        = 'layrshift-wordfence';
	public const UPDRAFTPLUS      = 'layrshift-updraftplus';

	/**
	 * @return array<string, string> Legacy slug => prefixed slug.
	 */
	public static function legacy_slug_map(): array {
		return array(
			'mcp-adapter'   => self::MCP_ADAPTER,
			'skill'         => self::SKILL,
			'code-execution' => self::CODE_EXECUTION,
			'filesystem'    => self::FILESYSTEM,
			'admin-access'  => self::ADMIN_ACCESS,
			'gutenberg'     => self::GUTENBERG,
			'elementor'     => self::ELEMENTOR,
			'prismshift'    => self::PRISMSHIFT,
			'yoast'         => self::YOAST,
			'smush'         => self::SMUSH,
			'vaultshift'    => self::VAULTSHIFT,
			'blogibot'      => self::BLOGIBOT,
			'wp-rocket'     => self::WP_ROCKET,
			'migrate-guru'  => self::MIGRATE_GURU,
			'litespeed'     => self::LITESPEED,
			'wp-optimize'   => self::WP_OPTIMIZE,
			'wp-fastest-cache' => self::WP_FASTEST_CACHE,
			'woocommerce'      => self::WOOCOMMERCE,
			'rank-math'        => self::RANK_MATH,
			'genesis'          => self::GENESIS,
			'astra'            => self::ASTRA,
			'contact-form-7'   => self::CONTACT_FORM_7,
			'wordfence'        => self::WORDFENCE,
			'updraftplus'      => self::UPDRAFTPLUS,
		);
	}
}
