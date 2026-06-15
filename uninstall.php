<?php
/**
 * Uninstall LAYRSHIFT.
 *
 * @package LayrShift
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'layrshift_settings' );
delete_option( 'layrshift_ability_log' );

$layrshift_sandbox = WP_CONTENT_DIR . '/layrshift-sandbox';
if ( is_dir( $layrshift_sandbox ) ) {
	// phpcs:disable WordPress.WP.AlternativeFunctions.file_system_operations_rmdir,WordPress.WP.AlternativeFunctions.unlink_unlink -- Recursive sandbox cleanup on uninstall.
	$layrshift_iterator = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator( $layrshift_sandbox, RecursiveDirectoryIterator::SKIP_DOTS ),
		RecursiveIteratorIterator::CHILD_FIRST
	);
	foreach ( $layrshift_iterator as $layrshift_item ) {
		if ( $layrshift_item->isDir() ) {
			rmdir( $layrshift_item->getPathname() );
		} else {
			unlink( $layrshift_item->getPathname() );
		}
	}
	rmdir( $layrshift_sandbox );
	// phpcs:enable
}
