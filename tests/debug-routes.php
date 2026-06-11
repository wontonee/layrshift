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

rest_get_server();
do_action( 'rest_api_init' );

foreach ( rest_get_server()->get_routes() as $route => $handlers ) {
	if ( str_contains( $route, 'layrshift' ) || str_contains( $route, 'mcp' ) ) {
		echo $route, PHP_EOL;
	}
}

echo PHP_EOL, 'LayrShift enabled: ', LayrShift\Plugin::is_abilities_enabled() ? 'yes' : 'no', PHP_EOL;
echo 'McpAdapter: ', class_exists( \WP\MCP\Core\McpAdapter::class ) ? 'yes' : 'no', PHP_EOL;
echo 'Requirements: ', LayrShift\Plugin::meets_requirements() ? 'yes' : 'no', PHP_EOL;
