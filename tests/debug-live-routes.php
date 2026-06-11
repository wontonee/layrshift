<?php
require dirname( __DIR__, 4 ) . '/wp-load.php';

$response = wp_remote_get(
	'https://wptestings.test/wp-json/',
	array( 'sslverify' => false, 'timeout' => 30 )
);

$body = wp_remote_retrieve_body( $response );
$data = json_decode( $body, true );

if ( ! is_array( $data ) || ! isset( $data['routes'] ) ) {
	echo "Failed to fetch routes\n";
	echo substr( (string) $body, 0, 500 );
	exit( 1 );
}

foreach ( array_keys( $data['routes'] ) as $route ) {
	if ( str_contains( $route, 'layrshift' ) || str_contains( $route, 'mcp' ) ) {
		echo $route, PHP_EOL;
	}
}
