<?php
/**
 * LayrShift MCP smoke test (run from site root).
 *
 * Usage: php wp-content/plugins/layrshift/tests/mcp-smoke.php
 *
 * @package LayrShift
 */

require_once dirname( __DIR__, 4 ) . '/wp-load.php';

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "Could not load WordPress.\n" );
	exit( 1 );
}

// Enable LayrShift for testing.
update_option(
	'layrshift_settings',
	array(
		'enabled'                => true,
		'allowed_user_ids'       => array(),
		'exec_time_limit'        => 30,
		'https_enforcement'      => false,
		'restrict_core_deletion' => true,
		'risk_acknowledged'      => true,
	)
);

$admins = get_users( array( 'role' => 'administrator', 'number' => 1 ) );
if ( empty( $admins ) ) {
	fwrite( STDERR, "No administrator user found.\n" );
	exit( 1 );
}

$user = $admins[0];
$app_name = 'LayrShift Smoke Test ' . gmdate( 'Y-m-d H:i:s' );
$password = WP_Application_Passwords::create_new_application_password(
	$user->ID,
	array( 'name' => $app_name )
);

if ( is_wp_error( $password ) ) {
	fwrite( STDERR, 'App password error: ' . $password->get_error_message() . "\n" );
	exit( 1 );
}

$app_password = $password[0];
$auth         = base64_encode( $user->user_login . ':' . $app_password );
$endpoint     = rest_url( 'layrshift/v1/mcp' );

echo "User: {$user->user_login}\n";
echo "Endpoint: {$endpoint}\n";
echo "App password (save if needed): {$app_password}\n\n";

/**
 * @param array<string, mixed> $body
 * @return array<string, mixed>
 */
function layrshift_mcp_post( string $url, string $auth, array $body, array $extra_headers = array() ): array {
	$headers = array_merge(
		array(
			'Authorization' => 'Basic ' . $auth,
			'Content-Type'  => 'application/json',
			'Accept'          => 'application/json, text/event-stream',
		),
		$extra_headers
	);

	$response = wp_remote_post(
		$url,
		array(
			'headers' => $headers,
			'body'    => wp_json_encode( $body ),
			'timeout' => 60,
			'sslverify' => false,
		)
	);

	if ( is_wp_error( $response ) ) {
		return array( 'error' => $response->get_error_message() );
	}

	return array(
		'code'    => wp_remote_retrieve_response_code( $response ),
		'headers' => wp_remote_retrieve_headers( $response ),
		'body'    => wp_remote_retrieve_body( $response ),
	);
}

$init = layrshift_mcp_post(
	$endpoint,
	$auth,
	array(
		'jsonrpc' => '2.0',
		'id'      => 1,
		'method'  => 'initialize',
		'params'  => array(
			'protocolVersion' => '2025-06-18',
			'capabilities'    => new stdClass(),
			'clientInfo'      => array(
				'name'    => 'layrshift-smoke-test',
				'version' => '1.0.0',
			),
		),
	)
);

echo "=== initialize ===\n";
echo 'HTTP ' . ( $init['code'] ?? '?' ) . "\n";
echo substr( (string) ( $init['body'] ?? '' ), 0, 2000 ) . "\n\n";

$session_id = '';
if ( ! empty( $init['headers']['mcp-session-id'] ) ) {
	$session_id = (string) $init['headers']['mcp-session-id'];
} elseif ( ! empty( $init['headers']['Mcp-Session-Id'] ) ) {
	$session_id = (string) $init['headers']['Mcp-Session-Id'];
}

$extra = $session_id ? array( 'Mcp-Session-Id' => $session_id ) : array();

$tools = layrshift_mcp_post(
	$endpoint,
	$auth,
	array(
		'jsonrpc' => '2.0',
		'id'      => 2,
		'method'  => 'tools/list',
		'params'  => new stdClass(),
	),
	$extra
);

echo "=== tools/list ===\n";
echo 'HTTP ' . ( $tools['code'] ?? '?' ) . "\n";
echo substr( (string) ( $tools['body'] ?? '' ), 0, 4000 ) . "\n\n";

$execute = layrshift_mcp_post(
	$endpoint,
	$auth,
	array(
		'jsonrpc' => '2.0',
		'id'      => 3,
		'method'  => 'tools/call',
		'params'  => array(
			'name'      => 'ls-execute-php',
			'arguments' => array(
				'code' => 'return get_bloginfo("name");',
			),
		),
	),
	$extra
);

echo "=== tools/call execute-php ===\n";
echo 'HTTP ' . ( $execute['code'] ?? '?' ) . "\n";
echo substr( (string) ( $execute['body'] ?? '' ), 0, 4000 ) . "\n";
