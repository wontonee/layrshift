<?php
/**
 * MCP server instructions for LayrShift-connected agents.
 *
 * @package LayrShift
 */

declare( strict_types=1 );

namespace LayrShift;

/**
 * Builds environment and usage instructions returned by discover-abilities.
 */
final class Instructions {

	/**
	 * @return string
	 */
	public static function build(): string {
		$lines = array(
			'LayrShift (LayrSoft AI) gives you dev/staging access to this WordPress installation.',
			'',
			'## Environment',
			'',
			'WordPress ' . get_bloginfo( 'version' ) . ' — PHP ' . PHP_VERSION . ' — Locale: ' . get_locale(),
			'Active theme: ' . self::active_theme_label(),
			'',
		);

		if ( function_exists( 'get_plugins' ) ) {
			/** @var array<string, array{Name?: string, Version?: string}> $all_plugins */
			$all_plugins = get_plugins();
			if ( ! empty( $all_plugins ) ) {
				$lines[] = 'Installed plugins:';
				foreach ( $all_plugins as $plugin_file => $plugin_data ) {
					$name    = $plugin_data['Name'] ?? $plugin_file;
					$version = $plugin_data['Version'] ?? '';
					$suffix  = '' !== $version ? ' v' . $version : '';
					$active  = is_plugin_active( $plugin_file ) ? 'active' : 'inactive';
					$lines[] = '- ' . $name . $suffix . ' (' . $active . ')';
				}
				$lines[] = '';
			}
		}

		$lines = array_merge(
			$lines,
			array(
				'## WordPress-native development',
				'',
				'Prefer WordPress-native features to store and manage data.',
				'Do not hardcode content in PHP arrays when WordPress has a better mechanism:',
				'- Custom post types for structured content (unless a modeling plugin owns it)',
				'- Taxonomies for categorization',
				'- Post meta for additional data on posts',
				'- Options API for settings',
				'',
				'If ACF, JetEngine, Pods, WooCommerce, or similar plugins are active,',
				'use them for the tasks they own instead of duplicating their data models.',
				'',
				'## LayrShift usage',
				'',
				'- Start every session with `mcp-adapter/discover-abilities`',
				'- Dispatch tools via `mcp-adapter/execute-ability` (or call LayrShift tools directly on the LayrShift MCP server)',
				'- PHP files you write go to `wp-content/layrshift-sandbox/` only',
				'- Probe site state with `layrshift/execute-php` before making changes',
				'- Run WP-CLI with `layrshift/run-wp-cli`; poll async jobs with `layrshift/get-wp-cli-job`',
				'- Browser wp-admin access: `layrshift/create-admin-access-link` (one-time exchange)',
				'- Gutenberg block edits: use `layrshift/gutenberg-*` abilities and keep the Block Editor Queue page open (`admin.php?page=layrshift-gutenberg-finalize`) while static blocks finalize',
				'- If a skill description matches the request, call `layrshift/skill-get` first',
				'- Safe mode if sandbox PHP breaks the site: append `?layrshift-safe-mode=1` to any admin URL',
			)
		);

		/**
		 * Filter LayrShift MCP instructions before skills catalog injection.
		 *
		 * @param string $instructions Base instructions markdown.
		 */
		return (string) apply_filters( 'layrshift_discover_abilities_instructions', implode( "\n", $lines ) );
	}

	private static function active_theme_label(): string {
		$theme = wp_get_theme();
		if ( $theme->parent() ) {
			return $theme->get( 'Name' ) . ' (child of ' . $theme->parent()->get( 'Name' ) . ')';
		}

		return $theme->get( 'Name' );
	}
}
