<?php
/**
 * LayrShift MCP server registration.
 *
 * @package LayrShift
 */

declare( strict_types=1 );

namespace LayrShift\Api;

use LayrShift\AbilitiesRegistry;
use LayrShift\Auth;
use LayrShift\Plugin;
use WP\MCP\Core\McpAdapter;
use WP\MCP\Core\McpServer;
use WP\MCP\Infrastructure\ErrorHandling\ErrorLogMcpErrorHandler;
use WP\MCP\Infrastructure\Observability\NullMcpObservabilityHandler;
use WP\MCP\Transport\HttpTransport;

/**
 * Registers the LayrShift MCP server.
 */
final class ServerFactory {

	private const SERVER_ID = 'layrshift-dev-server';

	public static function register( McpAdapter $adapter ): void {
		if ( ! Plugin::is_abilities_enabled() ) {
			return;
		}

		if ( ! Plugin::meets_requirements() ) {
			return;
		}

		$tools   = AbilitiesRegistry::tool_names();
		$prompts = AbilitiesRegistry::prompt_names();
		if ( empty( $tools ) && empty( $prompts ) ) {
			return;
		}

		$result = $adapter->create_server(
			self::SERVER_ID,
			'layrshift',
			'v1/mcp',
			'LayrShift Dev Server',
			__( 'Full WordPress dev access for AI agents (dev/staging only).', 'layrshift' ),
			LAYRSHIFT_VERSION,
			array( HttpTransport::class ),
			ErrorLogMcpErrorHandler::class,
			NullMcpObservabilityHandler::class,
			$tools,
			array(),
			$prompts,
			array( Auth::class, 'check_mcp_transport_permission' )
		);

		if ( is_wp_error( $result ) ) {
			add_action(
				'admin_notices',
				static function () use ( $result ): void {
					echo '<div class="notice notice-error"><p>';
					echo esc_html( $result->get_error_message() );
					echo '</p></div>';
				}
			);
			return;
		}

		self::register_transport_routes( $adapter );
	}

	/**
	 * HttpTransport hooks rest_api_init@16, but the server is created at @15 — too late
	 * for routes to register in the same request. Register immediately when needed.
	 */
	private static function register_transport_routes( McpAdapter $adapter ): void {
		$servers = $adapter->get_servers();
		if ( ! isset( $servers[ self::SERVER_ID ] ) || ! $servers[ self::SERVER_ID ] instanceof McpServer ) {
			return;
		}

		$server    = $servers[ self::SERVER_ID ];
		$transport = new HttpTransport( $server->create_transport_context() );
		$transport->register_routes();
	}
}
