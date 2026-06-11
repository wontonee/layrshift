<?php
/**
 * Template Studio smoke test (run from site root).
 *
 * Usage:
 *   php wp-content/plugins/layrshift/tests/template-studio-smoke.php
 *   GEMINI_API_KEY=your-key php wp-content/plugins/layrshift/tests/template-studio-smoke.php
 *
 * @package LayrShift
 */

$root = dirname( __DIR__, 4 );
$maintenance_file = $root . '/.maintenance';
if ( file_exists( $maintenance_file ) ) {
	unlink( $maintenance_file );
}

require_once $root . '/wp-load.php';

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "Could not load WordPress.\n" );
	exit( 1 );
}

use LayrShift\Api\TemplateStudioEndpoint;
use LayrShift\Pro\ProSettings;
use LayrShift\Pro\TemplateGenerator;

$api_key = getenv( 'GEMINI_API_KEY' ) ?: getenv( 'LAYRSHIFT_GEMINI_API_KEY' );
if ( is_string( $api_key ) ) {
	$api_key = trim( $api_key );
} else {
	$api_key = '';
}

if ( '' === $api_key && ! empty( $argv[1] ) ) {
	$api_key = trim( (string) $argv[1] );
}

if ( '' !== $api_key ) {
	ProSettings::save(
		array(
			'enabled'        => true,
			'gemini_api_key' => $api_key,
			'gemini_model'   => 'gemini-2.0-flash',
			'default_editor' => 'gutenberg',
		)
	);
	echo "Configured Pro settings from environment/argument.\n";
} elseif ( ! ProSettings::is_configured() ) {
	fwrite( STDERR, "No Gemini API key. Set GEMINI_API_KEY or pass key as first argument.\n" );
	exit( 1 );
}

$admins = get_users( array( 'role' => 'administrator', 'number' => 1 ) );
if ( empty( $admins ) ) {
	fwrite( STDERR, "No administrator user found.\n" );
	exit( 1 );
}

wp_set_current_user( $admins[0]->ID );

echo "User: {$admins[0]->user_login}\n";
echo "Configured: " . ( ProSettings::is_configured() ? 'yes' : 'no' ) . "\n\n";

// REST route registration check.
do_action( 'rest_api_init' );
$routes = rest_get_server()->get_routes();
$expected = array(
	'/layrshift/v1/templates/editors',
	'/layrshift/v1/templates/settings',
	'/layrshift/v1/templates/generate',
	'/layrshift/v1/templates/create',
);

foreach ( $expected as $route ) {
	$ok = isset( $routes[ $route ] ) ? 'OK' : 'MISSING';
	echo "Route {$route}: {$ok}\n";
}

$editors_request = new WP_REST_Request( 'GET', '/layrshift/v1/templates/editors' );
$editors_response = rest_get_server()->dispatch( $editors_request );
$editors_code = (int) $editors_response->get_status();
echo "\nGET /templates/editors: HTTP {$editors_code}\n";
if ( 200 !== $editors_code ) {
	fwrite( STDERR, wp_json_encode( $editors_response->get_data(), JSON_PRETTY_PRINT ) . "\n" );
	exit( 1 );
}

$prompt = 'Simple landing page for a bakery with a hero heading, short intro paragraph, and a call-to-action button.';
echo "\nGenerating template via TemplateGenerator…\n";

$generated = TemplateGenerator::generate( $prompt, 'gutenberg', 'Bakery Landing' );
if ( is_wp_error( $generated ) ) {
	fwrite( STDERR, 'Generate failed: ' . $generated->get_error_message() . "\n" );
	exit( 1 );
}

$content_preview = substr( (string) $generated['content'], 0, 240 );
echo "Editor: {$generated['editor']}\n";
echo "Title: {$generated['title']}\n";
echo "Content preview: {$content_preview}…\n";
echo 'Content length: ' . strlen( (string) $generated['content'] ) . " bytes\n";

if ( false === strpos( (string) $generated['content'], 'wp:' ) ) {
	fwrite( STDERR, "Generated content does not look like Gutenberg block markup.\n" );
	exit( 1 );
}

echo "\nCreating draft page…\n";
$created = TemplateGenerator::create_draft(
	(string) $generated['title'],
	(string) $generated['content'],
	(string) $generated['editor']
);

if ( is_wp_error( $created ) ) {
	fwrite( STDERR, 'Create failed: ' . $created->get_error_message() . "\n" );
	exit( 1 );
}

echo "Draft post ID: {$created['post_id']}\n";
echo "Edit URL: {$created['edit_url']}\n";
echo "\nTemplate Studio smoke test passed.\n";
