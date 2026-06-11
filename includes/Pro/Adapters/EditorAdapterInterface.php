<?php
/**
 * Editor adapter contract for Template Studio.
 *
 * @package LayrShift
 */

declare( strict_types=1 );

namespace LayrShift\Pro\Adapters;

interface EditorAdapterInterface {

	public function get_slug(): string;

	public function is_available(): bool;

	public function get_system_prompt(): string;

	/**
	 * @return string|\WP_Error Valid editor content.
	 */
	public function normalize_generated_content( string $raw );

	/**
	 * @return array{post_id: int, edit_url: string}|\WP_Error
	 */
	public function create_draft( string $title, string $content );
}
