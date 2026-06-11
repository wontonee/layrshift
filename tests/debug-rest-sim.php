<?php
require dirname( __DIR__, 4 ) . '/wp-load.php';

global $wp_rest_server;
echo 'wp_rest_server before: ', empty( $wp_rest_server ) ? 'empty' : 'set', PHP_EOL;

$has_hook = has_action( 'rest_api_init', array( \WP\MCP\Core\McpAdapter::instance(), 'init' ) );
echo 'mcp rest_api_init hook: ', $has_hook ? (string) $has_hook : 'none', PHP_EOL;

define( 'REST_REQUEST', true );
rest_get_server();

echo 'wp_rest_server after: ', empty( $wp_rest_server ) ? 'empty' : 'set', PHP_EOL;
echo 'did rest_api_init: ', did_action( 'rest_api_init' ) ? 'yes' : 'no', PHP_EOL;
echo 'servers: ', implode( ', ', array_keys( \WP\MCP\Core\McpAdapter::instance()->get_servers() ) ), PHP_EOL;

foreach ( rest_get_server()->get_routes() as $route => $handlers ) {
	if ( str_contains( $route, 'layrshift' ) ) {
		echo $route, PHP_EOL;
	}
}
