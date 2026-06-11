<?php
/**
 * Google Gemini API client for Template Studio.
 *
 * @package LayrShift
 */

declare( strict_types=1 );

namespace LayrShift\Pro;

final class GeminiClient {

	/**
	 * @param array<int, array{role: string, parts: array<int, array{text: string}>}> $contents
	 * @return string|\WP_Error
	 */
	public static function generate( string $api_key, string $model, array $contents, float $temperature = 0.4 ) {
		$api_key = trim( $api_key );
		if ( '' === $api_key ) {
			return new \WP_Error( 'layrshift_no_api_key', __( 'Gemini API key is not configured.', 'layrshift' ) );
		}

		$model = sanitize_text_field( $model );
		if ( '' === $model ) {
			$model = 'gemini-2.0-flash';
		}

		$url = sprintf(
			'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent?key=%s',
			rawurlencode( $model ),
			rawurlencode( $api_key )
		);

		$body = array(
			'contents'         => $contents,
			'generationConfig' => array(
				'temperature'     => $temperature,
				'maxOutputTokens' => 8192,
			),
		);

		$response = wp_remote_post(
			$url,
			array(
				'timeout' => 90,
				'headers' => array(
					'Content-Type' => 'application/json',
				),
				'body'    => wp_json_encode( $body ),
			)
		);

		if ( is_wp_error( $response ) ) {
			return new \WP_Error(
				'layrshift_gemini_request_failed',
				sprintf(
					/* translators: %s: error message */
					__( 'Gemini request failed: %s', 'layrshift' ),
					$response->get_error_message()
				)
			);
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$raw  = (string) wp_remote_retrieve_body( $response );
		$data = json_decode( $raw, true );

		if ( $code < 200 || $code >= 300 ) {
			$message = self::extract_error_message( $data, $raw );
			return new \WP_Error(
				'layrshift_gemini_http_error',
				sprintf(
					/* translators: 1: HTTP status code, 2: error message */
					__( 'Gemini API error (%1$d): %2$s', 'layrshift' ),
					$code,
					$message
				),
				array( 'status' => $code >= 400 && $code < 600 ? $code : 502 )
			);
		}

		if ( ! is_array( $data ) ) {
			return new \WP_Error( 'layrshift_gemini_invalid_response', __( 'Invalid response from Gemini.', 'layrshift' ) );
		}

		$text = self::extract_text( $data );
		if ( '' === $text ) {
			return new \WP_Error( 'layrshift_gemini_empty_response', __( 'Gemini returned no content.', 'layrshift' ) );
		}

		return $text;
	}

	/**
	 * @param array<string, mixed>|null $data
	 */
	private static function extract_error_message( ?array $data, string $raw ): string {
		if ( is_array( $data ) && ! empty( $data['error']['message'] ) ) {
			return (string) $data['error']['message'];
		}

		return wp_strip_all_tags( substr( $raw, 0, 300 ) );
	}

	/**
	 * @param array<string, mixed> $data
	 */
	private static function extract_text( array $data ): string {
		$candidates = $data['candidates'] ?? array();
		if ( ! is_array( $candidates ) || empty( $candidates[0]['content']['parts'] ) ) {
			return '';
		}

		$parts = $candidates[0]['content']['parts'];
		if ( ! is_array( $parts ) ) {
			return '';
		}

		$chunks = array();
		foreach ( $parts as $part ) {
			if ( is_array( $part ) && isset( $part['text'] ) ) {
				$chunks[] = (string) $part['text'];
			}
		}

		return trim( implode( "\n", $chunks ) );
	}
}
