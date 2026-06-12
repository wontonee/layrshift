<?php
/**
 * Plugin Name:       LayrShift
 * Plugin URI:        https://github.com/wontonee/layrshift
 * Description:       MCP server for WordPress dev and staging sites. Gives AI agents secure PHP, filesystem, and Gutenberg abilities via the Model Context Protocol.
 * Version:           1.0.1
 * Requires at least: 6.9
 * Requires PHP:      8.0
 * Author:            Saju Gopal / Wontonee DigitalCraft LLP
 * Author URI:        https://wontonee.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       layrshift
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
define( 'LAYRSHIFT_VERSION', $layrshift_plugin_data['Version'] ?: '1.0.1' );
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
	define( 'WP_MCP_AUTOLOAD', false );
}

$mcp_adapter_bootstrap = LAYRSHIFT_PATH . 'vendor/wordpress/mcp-adapter/mcp-adapter.php';
if ( file_exists( $mcp_adapter_bootstrap ) ) {
	require_once $mcp_adapter_bootstrap;
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
