<?php
/**
 * Main LayrShift app shell.
 *
 * @package LayrShift
 *
 * @var string               $active_tab
 * @var array<string, mixed> $pro_settings
 * @var array<string, mixed> $settings
 * @var array<int, object>   $admins
 * @var array<int, string>   $errors
 * @var string               $mcp_url
 * @var string               $setup_prompt
 * @var array<string, mixed> $mcp_connect
 */

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- View template scope.

$shell_mode = 'app';
include LAYRSHIFT_PATH . 'admin/views/partials/app-shell-open.php';

$tab_file = LAYRSHIFT_PATH . 'admin/views/tabs/' . $active_tab . '.php';
if ( file_exists( $tab_file ) ) {
	include $tab_file;
}

include LAYRSHIFT_PATH . 'admin/views/partials/app-shell-close.php';
