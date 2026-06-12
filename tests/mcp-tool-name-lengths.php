<?php
/**
 * Verify MCP tool names fit Cursor's server:tool length budget.
 *
 * Usage: php wp-content/plugins/layrshift/tests/mcp-tool-name-lengths.php
 *
 * @package LayrShift
 */

require_once dirname( __DIR__, 4 ) . '/wp-load.php';

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "Could not load WordPress.\n" );
	exit( 1 );
}

use LayrShift\AbilitiesRegistry;
use LayrShift\McpToolNames;

$worst_server = str_repeat( 'x', McpToolNames::RECOMMENDED_MAX_SERVER_NAME_LENGTH );
$failures     = array();

foreach ( AbilitiesRegistry::tool_names() as $ability_name ) {
	$ability = wp_get_ability( $ability_name );
	if ( ! $ability instanceof \WP_Ability ) {
		continue;
	}

	$sanitized = str_replace( '/', '-', $ability->get_name() );
	$tool_name = McpToolNames::shorten_tool_name( $sanitized, $ability );
	$combined  = $worst_server . ':' . $tool_name;
	$length    = strlen( $combined );

	if ( $length > McpToolNames::CURSOR_MAX_COMBINED_LENGTH ) {
		$failures[] = array(
			'ability'  => $ability->get_name(),
			'tool'     => $tool_name,
			'combined' => $combined,
			'length'   => $length,
		);
	}
}

if ( array() !== $failures ) {
	fwrite( STDERR, "MCP tool name length check FAILED\n" );
	foreach ( $failures as $row ) {
		fprintf(
			STDERR,
			"%d chars — %s (%s)\n",
			$row['length'],
			$row['tool'],
			$row['ability']
		);
	}
	exit( 1 );
}

$longest = McpToolNames::longest_tool_name_length();
echo "OK — all MCP tools fit Cursor limit with server name up to "
	. McpToolNames::RECOMMENDED_MAX_SERVER_NAME_LENGTH
	. " chars. Longest tool alias: {$longest} chars.\n";
