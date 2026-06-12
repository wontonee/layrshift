<?php
/**
 * Short MCP tool names for strict client limits (e.g. Cursor 60-char server:tool).
 *
 * @package LayrShift
 */

declare( strict_types=1 );

namespace LayrShift;

/**
 * Applies compact MCP tool aliases while ability IDs stay unchanged.
 */
final class McpToolNames {

	/**
	 * Cursor IDE combined server:tool name budget.
	 */
	public const CURSOR_MAX_COMBINED_LENGTH = 60;

	/**
	 * Recommended max mcp.json server key length (leaves room for longest tool alias).
	 */
	public const RECOMMENDED_MAX_SERVER_NAME_LENGTH = 16;

	/**
	 * @return void
	 */
	public static function init(): void {
		add_filter( 'mcp_adapter_tool_name', array( self::class, 'shorten_tool_name' ), 10, 2 );
	}

	/**
	 * @param string           $name    Sanitized MCP tool name from the adapter.
	 * @param \WP_Ability|null $ability Source ability (unused; reserved for filters).
	 */
	public static function shorten_tool_name( string $name, ?\WP_Ability $ability = null ): string {
		unset( $ability );

		foreach ( self::prefix_replacements() as $from => $to ) {
			if ( str_starts_with( $name, $from ) ) {
				return $to . substr( $name, strlen( $from ) );
			}
		}

		return $name;
	}

	/**
	 * Longest published MCP tool name after shortening (for admin hints).
	 */
	public static function longest_tool_name_length(): int {
		$max = 0;

		if ( ! class_exists( AbilitiesRegistry::class ) ) {
			return $max;
		}

		foreach ( AbilitiesRegistry::tool_names() as $ability_name ) {
			$ability = wp_get_ability( $ability_name );
			if ( ! $ability instanceof \WP_Ability ) {
				continue;
			}

			$sanitized = str_replace( '/', '-', $ability->get_name() );
			$short     = self::shorten_tool_name( $sanitized, $ability );
			$max       = max( $max, strlen( $short ) );
		}

		return $max;
	}

	/**
	 * @return array<string, string>
	 */
	private static function prefix_replacements(): array {
		return array(
			'layrshift-gutenberg-'  => 'ls-gb-',
			'layrshift-vaultshift-' => 'ls-vs-',
			'layrshift-elementor-'  => 'ls-el-',
			'layrshift-blogibot-'   => 'ls-bb-',
			'layrshift-yoast-'      => 'ls-yo-',
			'layrshift-smush-'      => 'ls-sm-',
			'layrshift-'            => 'ls-',
		);
	}
}
