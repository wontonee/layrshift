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

// Simulate REST request bootstrap without double init.
define( 'REST_REQUEST', true );
/** @var WP_REST_Server $server */
global $wp_rest_server;
$wp_rest_server = null;

rest_get_server();
do_action( 'rest_api_init' );

$result = null;
add_action(
	'mcp_adapter_init',
	static function ( $adapter ) use ( &$result ) {
		$tools = LayrShift\AbilitiesRegistry::tool_names();
		$result = $adapter->create_server(
			'layrshift-dev-server-test',
			'layrshift',
			'v1/mcp-test',
			'Test',
			'Test',
			'1.0.0',
			array( \WP\MCP\Transport\HttpTransport::class ),
			\WP\MCP\Infrastructure\ErrorHandling\ErrorLogMcpErrorHandler::class,
			\WP\MCP\Infrastructure\Observability\NullMcpObservabilityHandler::class,
			$tools,
			array(),
			array(),
			array( LayrShift\Auth::class, 'check_mcp_transport_permission' )
		);
	},
	999
);

\WP\MCP\Core\McpAdapter::instance()->init();

echo 'create_server result: ';
var_export( $result );
echo PHP_EOL;

foreach ( rest_get_server()->get_routes() as $route => $handlers ) {
	if ( str_contains( $route, 'layrshift' ) ) {
		echo $route, PHP_EOL;
	}

}
