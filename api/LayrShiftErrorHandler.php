<?php
/**
 * MCP error handler that logs to LayrShift logger.
 *
 * @package LayrShift
 */

declare( strict_types=1 );

namespace LayrShift\Api;

use WP\MCP\Infrastructure\ErrorHandling\Contracts\McpErrorHandlerInterface;

final class LayrShiftErrorHandler implements McpErrorHandlerInterface {

	public function log( string $message, array $context = array(), string $type = 'error' ): void {
		if ( function_exists( 'error_log' ) ) {
			error_log( sprintf( '[LayrShift MCP %s] %s %s', strtoupper( $type ), $message, wp_json_encode( $context ) ) );
		}
	}
}
