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
	public const YOAST         = 'layrshift-yoast';
	public const SMUSH         = 'layrshift-smush';
	public const VAULTSHIFT    = 'layrshift-vaultshift';
	public const BLOGIBOT      = 'layrshift-blogibot';

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
			'yoast'         => self::YOAST,
			'smush'         => self::SMUSH,
			'vaultshift'    => self::VAULTSHIFT,
			'blogibot'      => self::BLOGIBOT,
		);
	}
}
