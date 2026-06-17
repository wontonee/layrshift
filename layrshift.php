<?php

// SPDX-FileCopyrightText: 2026 Wontonee DigitalCraft LLP <dev@wontonee.com>
// SPDX-License-Identifier: AGPL-3.0-or-later

/**
 * Plugin Name:       LayrShift
 * Plugin URI:        https://github.com/wontonee/layrshift
 * Description:       MCP server for WordPress dev and staging sites. Gives AI agents secure PHP, filesystem, and Gutenberg abilities via the Model Context Protocol.
 * Version:           1.0.8
 * Requires at least: 6.9
 * Requires PHP:      8.0
 * Author:            Saju Gopal / Wontonee DigitalCraft LLP
 * Author URI:        https://wontonee.com
 * License:           AGPL-3.0-or-later
 * License URI:       https://www.gnu.org/licenses/agpl-3.0.html
 * Text Domain:       layrshift
 * Copyright:         Wontonee DigitalCraft LLP
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * @package LayrShift
 */

defined( 'ABSPATH' ) || exit;

$layrshift_plugin_data = get_file_data(
	__FILE__,
	array(
		'Version' => 'Version',
	)
);
define( 'LAYRSHIFT_VERSION', $layrshift_plugin_data['Version'] ?: '1.0.8' );
define( 'LAYRSHIFT_FILE', __FILE__ );
define( 'LAYRSHIFT_PATH', plugin_dir_path( __FILE__ ) );
define( 'LAYRSHIFT_URL', plugin_dir_url( __FILE__ ) );

$layrshift_autoload = LAYRSHIFT_PATH . 'vendor/autoload.php';
if ( ! file_exists( $layrshift_autoload ) ) {
	add_action(
		'admin_notices',
		static function (): void {
			echo '<div class="notice notice-error"><p>';
			esc_html_e( 'LayrShift: Run composer install in the plugin directory.', 'layrshift' );
			echo '</p></div>';
		}
	);
	return;
}

require_once $layrshift_autoload;

// Bundled mcp-adapter expects vendor/autoload.php inside its own package directory.
// LayrShift already loads WP\MCP\ classes via the plugin-level Composer autoload above.
if ( ! defined( 'WP_MCP_AUTOLOAD' ) ) {
	// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound -- Required by bundled mcp-adapter.
	define( 'WP_MCP_AUTOLOAD', false );
}

$layrshift_mcp_adapter_bootstrap = LAYRSHIFT_PATH . 'vendor/wordpress/mcp-adapter/mcp-adapter.php';
if ( file_exists( $layrshift_mcp_adapter_bootstrap ) ) {
	require_once $layrshift_mcp_adapter_bootstrap;
} elseif ( ! class_exists( \WP\MCP\Core\McpAdapter::class ) ) {
	add_action(
		'admin_notices',
		static function (): void {
			echo '<div class="notice notice-error"><p>';
			esc_html_e( 'LayrShift: MCP Adapter is missing. Run composer install in the plugin directory.', 'layrshift' );
			echo '</p></div>';
		}
	);
}

require_once LAYRSHIFT_PATH . 'includes/Plugin.php';

register_activation_hook( __FILE__, array( \LayrShift\Plugin::class, 'activate' ) );
register_deactivation_hook( __FILE__, array( \LayrShift\Plugin::class, 'deactivate' ) );

\LayrShift\Plugin::instance();
