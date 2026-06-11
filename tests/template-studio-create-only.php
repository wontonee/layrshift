<?php
$root = dirname( __DIR__, 4 );
if ( file_exists( $root . '/.maintenance' ) ) {
	unlink( $root . '/.maintenance' );
}
require_once $root . '/wp-load.php';

use LayrShift\Pro\TemplateGenerator;

wp_set_current_user( get_users( array( 'role' => 'administrator', 'number' => 1 ) )[0]->ID );

$content = <<<'HTML'
<!-- wp:heading {"level":1} -->
<h1>LAYRSHIFT Test Page</h1>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Template Studio draft creation smoke test.</p>
<!-- /wp:paragraph -->
HTML;

$result = TemplateGenerator::create_draft( 'LAYRSHIFT Studio Test', $content, 'gutenberg' );
if ( is_wp_error( $result ) ) {
	fwrite( STDERR, $result->get_error_message() . PHP_EOL );
	exit( 1 );
}

echo 'Draft post ID: ' . $result['post_id'] . PHP_EOL;
echo 'Edit URL: ' . $result['edit_url'] . PHP_EOL;
