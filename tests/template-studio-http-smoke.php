<?php
/**
 * HTTP-based Template Studio smoke test (works even when CLI hits maintenance).
 *
 * Usage: php wp-content/plugins/layrshift/tests/template-studio-http-smoke.php
 *
 * @package LayrShift
 */

require_once dirname( __DIR__, 4 ) . '/wp-load.php';

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "Could not load WordPress.\n" );
	exit( 1 );
}

use LayrShift\Pro\ProSettings;

if ( ! ProSettings::is_configured() ) {
	fwrite( STDERR, "Template Studio is not configured. Save Gemini key in admin first.\n" );
	exit( 1 );
}

$admins = get_users( array( 'role' => 'administrator', 'number' => 1 ) );
if ( empty( $admins ) ) {
	fwrite( STDERR, "No administrator user found.\n" );
	exit( 1 );
}

$user = $admins[0];
wp_set_current_user( $user->ID );

$nonce = wp_create_nonce( 'wp_rest' );
$base  = 'https://wptestings.test/wp-json/layrshift/v1';

/**
 * @return array<string, mixed>
 */
function layrshift_rest( string $url, string $method, string $nonce, ?array $body = null ): array {
	$response = wp_remote_request(
		$url,
		array(
			'method'  => $method,
			'timeout' => 120,
			'headers' => array(
				'Content-Type'  => 'application/json',
				'X-WP-Nonce'      => $nonce,
				'Cookie'          => LOGGED_IN_COOKIE . '=' . wp_generate_auth_cookie( get_current_user_id(), time() + HOUR_IN_SECONDS, 'logged_in' ),
			),
			'body'    => null !== $body ? wp_json_encode( $body ) : null,
			'sslverify' => false,
		)
	);

	if ( is_wp_error( $response ) ) {
		fwrite( STDERR, $response->get_error_message() . "\n" );
		exit( 1 );
	}

	$code = (int) wp_remote_retrieve_response_code( $response );
	$raw  = (string) wp_remote_retrieve_body( $response );
	$data = json_decode( $raw, true );

	return array(
		'code' => $code,
		'data' => is_array( $data ) ? $data : array( 'raw' => $raw ),
	);
}

echo "User: {$user->user_login}\n";
echo "Configured: yes\n\n";

$editors = layrshift_rest( $base . '/templates/editors', 'GET', $nonce );
echo "GET /templates/editors: HTTP {$editors['code']}\n";
if ( 200 !== $editors['code'] ) {
	fwrite( STDERR, wp_json_encode( $editors['data'], JSON_PRETTY_PRINT ) . "\n" );
	exit( 1 );
}

$prompt = 'Simple landing page for a bakery with hero heading, intro paragraph, and a call-to-action button.';
echo "\nPOST /templates/generate …\n";
$generated = layrshift_rest(
	$base . '/templates/generate',
	'POST',
	$nonce,
	array(
		'prompt' => $prompt,
		'title'  => 'Bakery Landing Test',
		'editor' => 'gutenberg',
	)
);

echo "HTTP {$generated['code']}\n";
if ( 200 !== $generated['code'] ) {
	$message = $generated['data']['message'] ?? wp_json_encode( $generated['data'] );
	fwrite( STDERR, "Generate failed: {$message}\n" );
	exit( 1 );
}

$content = (string) ( $generated['data']['content'] ?? '' );
$title   = (string) ( $generated['data']['title'] ?? 'Bakery Landing Test' );
echo "Title: {$title}\n";
echo 'Content length: ' . strlen( $content ) . " bytes\n";
echo 'Has block markup: ' . ( false !== strpos( $content, 'wp:' ) ? 'yes' : 'no' ) . "\n";

if ( '' === $content || false === strpos( $content, 'wp:' ) ) {
	fwrite( STDERR, "Generated content is not valid Gutenberg markup.\n" );
	exit( 1 );
}

echo "\nPOST /templates/create …\n";
$created = layrshift_rest(
	$base . '/templates/create',
	'POST',
	$nonce,
	array(
		'title'   => $title,
		'content' => $content,
		'editor'  => 'gutenberg',
	)
);

echo "HTTP {$created['code']}\n";
if ( 200 !== $created['code'] ) {
	$message = $created['data']['message'] ?? wp_json_encode( $created['data'] );
	fwrite( STDERR, "Create failed: {$message}\n" );
	exit( 1 );
}

echo "Draft post ID: {$created['data']['post_id']}\n";
echo "Edit URL: {$created['data']['edit_url']}\n";
echo "\nTemplate Studio HTTP smoke test passed.\n";
