<?php
/**
 * MCP observability handler wired to LayrShift logger.
 *
 * @package LayrShift
 */

declare( strict_types=1 );

namespace LayrShift\Api;

use LayrShift\Logger;
use WP\MCP\Infrastructure\Observability\Contracts\McpObservabilityHandlerInterface;

final class LayrShiftObservabilityHandler implements McpObservabilityHandlerInterface {

	public function record_event( string $event, array $tags = array(), ?float $duration_ms = null ): void {
		Logger::log(
			'mcp/' . $event,
			$tags,
			array( 'duration_ms' => $duration_ms ),
			true,
			$duration_ms ?? 0.0
		);
	}
}
