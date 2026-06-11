<?php
require_once dirname( __DIR__, 4 ) . '/wp-load.php';
do_action( 'rest_api_init' );
$routes = rest_get_server()->get_routes();
foreach ( array(
	'/layrshift/v1/templates/editors',
	'/layrshift/v1/templates/settings',
	'/layrshift/v1/templates/generate',
	'/layrshift/v1/templates/create',
) as $path ) {
	echo $path . ': ' . ( isset( $routes[ $path ] ) ? 'OK' : 'MISSING' ) . PHP_EOL;
}
$admins = get_users( array( 'role' => 'administrator', 'number' => 1 ) );
wp_set_current_user( $admins[0]->ID );
$req = new WP_REST_Request( 'GET', '/layrshift/v1/templates/editors' );
$res = rest_get_server()->dispatch( $req );
echo 'editors status: ' . $res->get_status() . PHP_EOL;
echo wp_json_encode( $res->get_data(), JSON_PRETTY_PRINT ) . PHP_EOL;
