<?php
/**
 * Temporary upload REST endpoint.
 *
 * @package LayrShift
 */

declare( strict_types=1 );

namespace LayrShift\Api;

use LayrShift\AbilityHelpers;
use LayrShift\PathHelper;
use LayrShift\UploadToken;

final class UploadEndpoint {

	public static function register_routes(): void {
		register_rest_route(
			'layrshift/v1',
			'/upload',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( self::class, 'handle_upload' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * @return \WP_REST_Response|\WP_Error
	 */
	public static function handle_upload( \WP_REST_Request $request ) {
		$token = AbilityHelpers::rest_header_token( $request, 'x-layrshift-upload-token' );
		if ( '' === $token ) {
			return new \WP_Error(
				'layrshift_missing_token',
				__( 'Upload token is required in the X-LayrShift-Upload-Token request header.', 'layrshift' ),
				array( 'status' => 400 )
			);
		}

		$payload = UploadToken::consume( $token );
		if ( is_wp_error( $payload ) ) {
			return $payload;
		}

		$files = $request->get_file_params();
		if ( empty( $files['file'] ) || ! is_array( $files['file'] ) ) {
			return new \WP_Error( 'layrshift_missing_file', __( 'No file uploaded.', 'layrshift' ), array( 'status' => 400 ) );
		}

		$file = $files['file'];
		if ( ! empty( $file['error'] ) ) {
			return new \WP_Error( 'layrshift_upload_error', __( 'File upload failed.', 'layrshift' ), array( 'status' => 400 ) );
		}

		$dest_dir = UploadToken::resolve_destination_path( $payload );
		if ( is_wp_error( $dest_dir ) ) {
			return $dest_dir;
		}

		if ( ! is_dir( $dest_dir ) && ! wp_mkdir_p( $dest_dir ) ) {
			return new \WP_Error( 'layrshift_dest_unwritable', __( 'Destination directory is not writable.', 'layrshift' ), array( 'status' => 500 ) );
		}

		$filename = sanitize_file_name( basename( (string) $file['name'] ) );
		$target   = trailingslashit( $dest_dir ) . $filename;

		$php_guard = PathHelper::assert_php_write_allowed( $target );
		if ( is_wp_error( $php_guard ) ) {
			return $php_guard;
		}

		$tmp_name = (string) ( $file['tmp_name'] ?? '' );
		if ( '' === $tmp_name || ! is_uploaded_file( $tmp_name ) ) {
			return new \WP_Error( 'layrshift_invalid_upload', __( 'Invalid uploaded file.', 'layrshift' ), array( 'status' => 400 ) );
		}

		$contents = file_get_contents( $tmp_name );
		if ( false === $contents ) {
			return new \WP_Error( 'layrshift_read_failed', __( 'Could not read uploaded file.', 'layrshift' ), array( 'status' => 500 ) );
		}

		if ( false === file_put_contents( $target, $contents ) ) {
			return new \WP_Error( 'layrshift_move_failed', __( 'Could not save uploaded file.', 'layrshift' ), array( 'status' => 500 ) );
		}

		@unlink( $tmp_name );

		return rest_ensure_response(
			array(
				'success'  => true,
				'path'     => wp_normalize_path( $target ),
				'filename' => $filename,
				'size'     => filesize( $target ),
			)
		);
	}
}
