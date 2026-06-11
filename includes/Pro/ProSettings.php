<?php
/**
 * LayrShift Pro (Template Studio) settings.
 *
 * @package LayrShift
 */

declare( strict_types=1 );

namespace LayrShift\Pro;

final class ProSettings {

	private const OPTION = 'layrshift_pro_settings';

	/**
	 * @return array<string, mixed>
	 */
	public static function get(): array {
		$defaults = self::defaults();
		$stored   = get_option( self::OPTION, array() );

		if ( ! is_array( $stored ) ) {
			$stored = array();
		}

		return array_merge( $defaults, $stored );
	}

	/**
	 * @param array<string, mixed> $input Raw settings from form or REST.
	 * @return array<string, mixed>
	 */
	public static function sanitize( array $input ): array {
		$current = self::get();

		$api_key = isset( $input['gemini_api_key'] ) ? trim( (string) $input['gemini_api_key'] ) : '';
		if ( '' === $api_key ) {
			$api_key = (string) ( $current['gemini_api_key'] ?? '' );
		}

		$model = sanitize_text_field( (string) ( $input['gemini_model'] ?? $current['gemini_model'] ) );
		if ( '' === $model ) {
			$model = self::defaults()['gemini_model'];
		}

		$editor = sanitize_key( (string) ( $input['default_editor'] ?? $current['default_editor'] ) );
		if ( ! in_array( $editor, array( 'auto', 'gutenberg', 'elementor' ), true ) ) {
			$editor = 'auto';
		}

		return array(
			'enabled'         => ! empty( $input['enabled'] ),
			'gemini_api_key'  => $api_key,
			'gemini_model'    => $model,
			'default_editor'  => $editor,
		);
	}

	/**
	 * @param array<string, mixed> $settings Sanitized settings.
	 */
	public static function save( array $settings ): void {
		update_option( self::OPTION, self::sanitize( $settings ) );
	}

	public static function is_configured(): bool {
		$settings = self::get();
		return ! empty( $settings['enabled'] ) && '' !== trim( (string) ( $settings['gemini_api_key'] ?? '' ) );
	}

	public static function has_api_key(): bool {
		return '' !== trim( (string) ( self::get()['gemini_api_key'] ?? '' ) );
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function defaults(): array {
		return array(
			'enabled'        => false,
			'gemini_api_key' => '',
			'gemini_model'   => 'gemini-2.0-flash',
			'default_editor' => 'auto',
		);
	}
}
