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
				'- Dispatch tools via `mcp-adapter/execute-ability` (or call LayrShift tools directly on the LayrShift MCP server; MCP tool names use compact `ls-*` aliases, e.g. `ls-gb-enable-batch-finalization` for `layrshift/gutenberg-enable-batch-finalization`)',
				'- PHP files you write go to `wp-content/layrshift-sandbox/` only',
				'- Browser wp-admin access: `layrshift/create-admin-access-link` (one-time exchange)',
				'- Gutenberg block edits: use `layrshift/gutenberg-*` abilities and keep the Block Editor Queue page open (`admin.php?page=layrshift-gutenberg-finalize`) while static blocks finalize',
				'- Elementor edits: use `layrshift/elementor-get-document`, `layrshift/elementor-save-document`, and `layrshift/elementor-list-templates` (load the `elementor` skill first); draft-only by default',
				'- Yoast SEO: `layrshift/yoast-get-post-seo`, `layrshift/yoast-update-post-seo`, `layrshift/yoast-get-site-settings` (load `yoast` skill)',
				'- Smush: `layrshift/smush-get-stats`, `layrshift/smush-list-unsmushed`, `layrshift/smush-optimize-attachment`, `layrshift/smush-run-bulk-smush` (load `smush` skill; confirm bulk runs)',
				'- WP Rocket: `layrshift/wp-rocket-get-status`, `layrshift/wp-rocket-get-settings`, `layrshift/wp-rocket-clear-cache` (load `wp-rocket` skill)',
				'- LiteSpeed Cache: `layrshift/litespeed-get-status`, `layrshift/litespeed-get-settings`, `layrshift/litespeed-purge-all` (load `litespeed` skill)',
				'- WP-Optimize: `layrshift/wp-optimize-get-status`, `layrshift/wp-optimize-get-settings`, `layrshift/wp-optimize-purge-cache` (load `wp-optimize` skill)',
				'- WP Fastest Cache: `layrshift/wp-fastest-cache-get-status`, `layrshift/wp-fastest-cache-get-settings`, `layrshift/wp-fastest-cache-clear-cache` (load `wp-fastest-cache` skill)',
				'- Migrate Guru: `layrshift/migrate-guru-get-status`, `layrshift/migrate-guru-get-connection-info`, `layrshift/migrate-guru-get-migration-state` (load `migrate-guru` skill; read-only)',
				'- VaultShift: `layrshift/vaultshift-get-status`, `layrshift/vaultshift-trigger-scan`, `layrshift/vaultshift-list-activity` (load `vaultshift` skill)',
				'- BlogiBot: `layrshift/blogibot-get-status`, `layrshift/blogibot-list-posts`, `layrshift/blogibot-get-settings` (load `blogibot` skill when plugin active)',
				'- WooCommerce: `layrshift/woocommerce-get-status`, `layrshift/woocommerce-list-products`, `layrshift/woocommerce-get-product` (load `woocommerce` skill)',
				'- Rank Math: `layrshift/rank-math-get-post-seo`, `layrshift/rank-math-update-post-seo`, `layrshift/rank-math-get-site-settings` (load `rank-math` skill)',
				'- Genesis: `layrshift/genesis-get-status`, `layrshift/genesis-get-settings`, `layrshift/genesis-get-post-meta` (load `genesis` skill when Genesis parent theme active)',
				'- Astra: `layrshift/astra-get-status`, `layrshift/astra-get-settings`, `layrshift/astra-get-header-footer` (load `astra` skill)',
				'- Contact Form 7: `layrshift/contact-form-7-get-status`, `layrshift/contact-form-7-list-forms`, `layrshift/contact-form-7-get-form` (load `contact-form-7` skill)',
				'- Wordfence: `layrshift/wordfence-get-status`, `layrshift/wordfence-get-scan-summary`, `layrshift/wordfence-get-settings-summary` (load `wordfence` skill; read-only)',
				'- UpdraftPlus: `layrshift/updraftplus-get-status`, `layrshift/updraftplus-list-backups`, `layrshift/updraftplus-get-settings` (load `updraftplus` skill; read-only)',
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
