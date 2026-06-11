<?php
/**
 * Uninstall LAYRSHIFT.
 *
 * @package LayrShift
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'layrshift_settings' );
delete_option( 'layrshift_ability_log' );

$sandbox = WP_CONTENT_DIR . '/layrshift-sandbox';
if ( is_dir( $sandbox ) ) {
	$iterator = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator( $sandbox, RecursiveDirectoryIterator::SKIP_DOTS ),
		RecursiveIteratorIterator::CHILD_FIRST
	);
	foreach ( $iterator as $item ) {
		if ( $item->isDir() ) {
			rmdir( $item->getPathname() );
		} else {
			unlink( $item->getPathname() );
		}
	}
	rmdir( $sandbox );
}
