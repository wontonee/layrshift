<?php
/**
 * Orchestrates AI template generation per editor adapter.
 *
 * @package LayrShift
 */

declare( strict_types=1 );

namespace LayrShift\Pro;

use LayrShift\Pro\Adapters\EditorAdapterInterface;
use LayrShift\Pro\Adapters\ElementorAdapter;
use LayrShift\Pro\Adapters\GutenbergAdapter;

final class TemplateGenerator {

	/**
	 * @return array{editor: string, title: string, content: string, raw: string}|\WP_Error
	 */
	public static function generate( string $prompt, string $editor_preference = 'auto', string $title = '' ) {
		if ( ! ProSettings::is_configured() ) {
			return new \WP_Error(
				'layrshift_pro_not_configured',
				__( 'Enable Template Studio and add a Gemini API key in LayrShift settings.', 'layrshift' ),
				array( 'status' => 400 )
			);
		}

		$prompt = trim( $prompt );
		if ( '' === $prompt ) {
			return new \WP_Error( 'layrshift_missing_prompt', __( 'Describe the template you want to create.', 'layrshift' ), array( 'status' => 400 ) );
		}

		$editor = EditorDetector::resolve( $editor_preference );
		$adapter = self::adapter_for( $editor );
		if ( ! $adapter->is_available() ) {
			return new \WP_Error(
				'layrshift_editor_unavailable',
				__( 'The selected editor is not available on this site.', 'layrshift' ),
				array( 'status' => 400 )
			);
		}

		if ( 'elementor' === $editor ) {
			return new \WP_Error(
				'layrshift_elementor_not_ready',
				__( 'Elementor support is coming soon. Choose Gutenberg for now.', 'layrshift' ),
				array( 'status' => 400 )
			);
		}

		$settings = ProSettings::get();
		$user_message = $title !== ''
			? "Page title: {$title}\n\nUser request:\n{$prompt}"
			: "User request:\n{$prompt}";

		$contents = array(
			array(
				'role'  => 'user',
				'parts' => array(
					array( 'text' => $adapter->get_system_prompt() . "\n\n" . $user_message ),
				),
			),
		);

		$raw = GeminiClient::generate(
			(string) $settings['gemini_api_key'],
			(string) $settings['gemini_model'],
			$contents
		);

		if ( is_wp_error( $raw ) ) {
			return $raw;
		}

		$content = $adapter->normalize_generated_content( (string) $raw );
		if ( is_wp_error( $content ) ) {
			return $content;
		}

		$suggested_title = $title !== '' ? $title : self::suggest_title( $prompt );

		return array(
			'editor'  => $editor,
			'title'   => $suggested_title,
			'content' => (string) $content,
			'raw'     => (string) $raw,
		);
	}

	/**
	 * @return array{post_id: int, edit_url: string, editor: string, title: string}|\WP_Error
	 */
	public static function create_draft( string $title, string $content, string $editor ) {
		$adapter = self::adapter_for( $editor );
		if ( ! $adapter->is_available() ) {
			return new \WP_Error(
				'layrshift_editor_unavailable',
				__( 'The selected editor is not available on this site.', 'layrshift' ),
				array( 'status' => 400 )
			);
		}

		$normalized = $adapter->normalize_generated_content( $content );
		if ( is_wp_error( $normalized ) ) {
			return $normalized;
		}

		$result = $adapter->create_draft( $title, (string) $normalized );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return array_merge(
			$result,
			array(
				'editor' => $editor,
				'title'  => $title,
			)
		);
	}

	private static function adapter_for( string $editor ): EditorAdapterInterface {
		if ( 'elementor' === $editor ) {
			return new ElementorAdapter();
		}

		return new GutenbergAdapter();
	}

	private static function suggest_title( string $prompt ): string {
		$line = strtok( $prompt, "\n" );
		if ( false === $line ) {
			return __( 'AI Template', 'layrshift' );
		}

		$line = trim( $line );
		if ( '' === $line ) {
			return __( 'AI Template', 'layrshift' );
		}

		if ( function_exists( 'mb_substr' ) ) {
			return mb_substr( $line, 0, 80 );
		}

		return substr( $line, 0, 80 );
	}
}
