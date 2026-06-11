<?php
/**
 * Signed upload token manager.
 *
 * @package LayrShift
 */

declare( strict_types=1 );

namespace LayrShift;

/**
 * Single-use upload tokens.
 */
final class UploadToken {

	/**
	 * @return array<string, mixed>|\WP_Error
	 */
	public static function create( string $destination, ?string $custom_path, int $expires_seconds ) {
		$expires_seconds = max( 60, min( 3600, $expires_seconds ) );
		$token           = wp_generate_password( 32, false );
		$payload         = array(
			'destination'  => $destination,
			'custom_path'  => $custom_path,
			'user_id'      => get_current_user_id(),
			'created'      => time(),
			'expires'      => time() + $expires_seconds,
			'used'         => false,
		);

		set_transient( self::key( $token ), $payload, $expires_seconds );

		return array(
			'upload_url'  => rest_url( 'layrshift/v1/upload' ) . '?token=' . rawurlencode( $token ),
			'expires_at'  => gmdate( 'c', $payload['expires'] ),
			'method'      => 'POST',
			'field_name'  => 'file',
			'token'       => $token,
		);
	}

	/**
	 * @return array<string, mixed>|\WP_Error
	 */
	public static function consume( string $token ) {
		$data = get_transient( self::key( $token ) );
		if ( ! is_array( $data ) ) {
			return new \WP_Error( 'layrshift_invalid_token', __( 'Upload token is invalid or expired.', 'layrshift' ) );
		}

		if ( ! empty( $data['used'] ) ) {
			return new \WP_Error( 'layrshift_token_used', __( 'Upload token has already been used.', 'layrshift' ) );
		}

		$data['used'] = true;
		delete_transient( self::key( $token ) );

		return $data;
	}

	/**
	 * @return string|\WP_Error
	 */
	public static function resolve_destination_path( array $payload ) {
		$destination = $payload['destination'] ?? '';

		switch ( $destination ) {
			case 'plugins':
				return wp_normalize_path( WP_PLUGIN_DIR );
			case 'themes':
				return wp_normalize_path( get_theme_root() );
			case 'uploads':
				$upload = wp_upload_dir();
				return wp_normalize_path( $upload['basedir'] );
			case 'custom':
				$custom = $payload['custom_path'] ?? '';
				if ( '' === $custom ) {
					return new \WP_Error( 'layrshift_custom_path', __( 'custom_path is required for custom destination.', 'layrshift' ) );
				}
				return PathHelper::resolve( $custom );
			default:
				return new \WP_Error( 'layrshift_bad_destination', __( 'Invalid upload destination.', 'layrshift' ) );
		}
	}

	private static function key( string $token ): string {
		return 'layrshift_upload_' . md5( $token );
	}
}
