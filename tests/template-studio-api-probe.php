<?php
require_once dirname( __DIR__, 4 ) . '/wp-load.php';
use LayrShift\Pro\ProSettings;
use LayrShift\Pro\TemplateGenerator;

ProSettings::save(
	array(
		'enabled'        => true,
		'gemini_api_key' => 'invalid-test-key',
		'gemini_model'   => 'gemini-2.0-flash',
		'default_editor' => 'gutenberg',
	)
);

wp_set_current_user( get_users( array( 'role' => 'administrator', 'number' => 1 ) )[0]->ID );

$result = TemplateGenerator::generate( 'Simple hero page with heading and paragraph', 'gutenberg', 'Test Page' );
if ( is_wp_error( $result ) ) {
	echo 'API integration reachable: ' . $result->get_error_message() . PHP_EOL;
	exit( 0 );
}

echo "Unexpected success\n";
exit( 1 );
