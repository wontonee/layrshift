<?php
/**
 * Elementor adapter (planned for v2).
 *
 * @package LayrShift
 */

declare( strict_types=1 );

namespace LayrShift\Pro\Adapters;

use LayrShift\Pro\EditorDetector;

final class ElementorAdapter implements EditorAdapterInterface {

	public function get_slug(): string {
		return 'elementor';
	}

	public function is_available(): bool {
		return EditorDetector::is_elementor_available();
	}

	public function get_system_prompt(): string {
		return '';
	}

	/**
	 * @return string|\WP_Error
	 */
	public function normalize_generated_content( string $raw ) {
		return new \WP_Error(
			'layrshift_elementor_not_ready',
			__( 'Elementor template generation is coming soon. Use Gutenberg for now.', 'layrshift' )
		);
	}

	/**
	 * @return array{post_id: int, edit_url: string}|\WP_Error
	 */
	public function create_draft( string $title, string $content ) {
		return new \WP_Error(
			'layrshift_elementor_not_ready',
			__( 'Elementor template creation is coming soon. Use Gutenberg for now.', 'layrshift' )
		);
	}
}
