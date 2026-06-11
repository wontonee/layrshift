<?php
require dirname( __DIR__, 4 ) . '/wp-load.php';

update_option(
	'layrshift_settings',
	array(
		'enabled'                => true,
		'allowed_user_ids'       => array(),
		'exec_time_limit'        => 30,
		'https_enforcement'      => true,
		'restrict_core_deletion' => true,
		'risk_acknowledged'      => true,
	)
);

do_action( 'init' );
do_action( 'rest_api_init' );

$abilities = wp_get_abilities();
echo 'Registered LayrShift abilities:', PHP_EOL;
foreach ( array_keys( $abilities ) as $name ) {
	if ( str_starts_with( $name, 'layrshift/' ) ) {
		echo ' - ', $name, PHP_EOL;
	}
}

$adapter = \WP\MCP\Core\McpAdapter::instance();
$servers = $adapter->get_servers();
echo PHP_EOL, 'MCP servers: ', count( $servers ), PHP_EOL;
foreach ( $servers as $server ) {
	echo ' - ', $server->get_server_id(), ' => ', $server->get_server_route_namespace(), '/', $server->get_server_route(), PHP_EOL;
}

echo PHP_EOL, 'Routes:', PHP_EOL;
foreach ( rest_get_server()->get_routes() as $route => $handlers ) {
	if ( str_contains( $route, 'layrshift' ) || str_contains( $route, 'mcp' ) ) {
		echo $route, PHP_EOL;
	}
}
