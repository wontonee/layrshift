<?php
/**
 * Temporary signed admin access exchange for browser automation.
 *
 * @package LayrShift
 */

declare( strict_types=1 );

namespace LayrShift\Api;

use LayrShift\AbilityHelpers;
use LayrShift\Plugin;

/**
 * REST endpoints for one-time wp-admin browser login.
 */
final class AdminAccessEndpoint {

	public static function register_routes(): void {
		register_rest_route(
			'layrshift/v1',
			'/admin-access',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( self::class, 'handle_exchange' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			'layrshift/v1',
			'/admin-access/(?P<nonce>[A-Za-z0-9_-]+)',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( self::class, 'handle_login' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * @return array{token: string, nonce: string, expires_at: int}|\WP_Error
	 */
	public static function create_token( int $user_id, int $expires_in, int $session_expires_in, string $admin_path ) {
		$user = get_user_by( 'id', $user_id );
		if ( ! $user instanceof \WP_User || ! AbilityHelpers::user_can_manage( $user ) ) {
			return new \WP_Error( 'invalid_admin_access_user', 'Admin access links can only be created for administrators.' );
		}

		$redirect_url = self::resolve_redirect( $admin_path );
		if ( is_wp_error( $redirect_url ) ) {
			return $redirect_url;
		}

		$token      = wp_generate_password( 64, false, false );
		$nonce      = wp_generate_password( 32, false, false );
		$expires_at = time() + $expires_in;
		$payload    = array(
			'user_id'            => $user_id,
			'redirect_url'       => $redirect_url,
			'expires_at'         => $expires_at,
			'session_expires_in' => $session_expires_in,
			'nonce_hash'         => self::nonce_hash( $nonce ),
		);

		if ( ! set_transient( self::token_key( $token ), $payload, $expires_in ) ) {
			return new \WP_Error( 'admin_access_token_store_failed', 'Could not store admin access token.' );
		}

		return array(
			'token'      => $token,
			'nonce'      => $nonce,
			'expires_at' => $expires_at,
		);
	}

	/**
	 * @return \WP_REST_Response|\WP_Error
	 */
	public static function handle_exchange( \WP_REST_Request $request ) {
		if ( ! Plugin::is_abilities_enabled() ) {
			return new \WP_Error( 'layrshift_disabled', 'LayrShift abilities are disabled.', array( 'status' => 403 ) );
		}

		$token = AbilityHelpers::rest_header_token( $request, 'x-layrshift-admin-access-token' );
		if ( '' === $token ) {
			return new \WP_Error( 'missing_admin_access_token', 'Missing admin access token.', array( 'status' => 401 ) );
		}

		$nonce = trim( (string) $request->get_header( 'x-layrshift-admin-access-nonce' ) );
		if ( '' === $nonce ) {
			return new \WP_Error( 'missing_admin_access_nonce', 'Missing admin access nonce.', array( 'status' => 401 ) );
		}

		$payload = get_transient( self::token_key( $token ) );
		delete_transient( self::token_key( $token ) );

		if ( ! is_array( $payload ) || ! isset( $payload['nonce_hash'] ) || ! is_string( $payload['nonce_hash'] ) ) {
			return new \WP_Error( 'invalid_admin_access_token', 'Invalid or expired admin access token.', array( 'status' => 401 ) );
		}

		if ( ! hash_equals( $payload['nonce_hash'], self::nonce_hash( $nonce ) ) ) {
			return new \WP_Error( 'invalid_admin_access_token', 'Invalid or expired admin access token.', array( 'status' => 401 ) );
		}

		$access = self::validate_payload( $payload );
		if ( is_wp_error( $access ) ) {
			return $access;
		}

		$login_nonce      = wp_generate_password( 48, false, false );
		$login_expires_at = min( $access['expires_at'], time() + 60 );
		$login_payload    = array(
			'user_id'            => $access['user_id'],
			'redirect_url'       => $access['redirect_url'],
			'expires_at'         => $login_expires_at,
			'session_expires_in' => $access['session_expires_in'],
		);

		$login_expires_in = max( 1, $login_expires_at - time() );
		if ( ! set_transient( self::login_nonce_key( $login_nonce ), $login_payload, $login_expires_in ) ) {
			return new \WP_Error( 'admin_access_nonce_store_failed', 'Could not store admin access login nonce.' );
		}

		$response = new \WP_REST_Response(
			array(
				'login_url'          => rest_url( 'layrshift/v1/admin-access/' . rawurlencode( $login_nonce ) ),
				'expires_at'         => $login_expires_at,
				'session_expires_in' => $access['session_expires_in'],
				'redirect_url'       => $access['redirect_url'],
				'one_time'           => true,
			)
		);
		self::no_store_headers( $response );

		return $response;
	}

	/**
	 * @return \WP_REST_Response|\WP_Error
	 */
	public static function handle_login( \WP_REST_Request $request ) {
		if ( ! Plugin::is_abilities_enabled() ) {
			return new \WP_Error( 'layrshift_disabled', 'LayrShift abilities are disabled.', array( 'status' => 403 ) );
		}

		$nonce = (string) $request->get_param( 'nonce' );
		if ( '' === $nonce ) {
			return new \WP_Error( 'missing_admin_access_nonce', 'Missing admin access nonce.', array( 'status' => 401 ) );
		}

		$payload = get_transient( self::login_nonce_key( $nonce ) );
		delete_transient( self::login_nonce_key( $nonce ) );

		if ( ! is_array( $payload ) ) {
			return new \WP_Error( 'invalid_admin_access_nonce', 'Invalid or expired admin access nonce.', array( 'status' => 401 ) );
		}

		$access = self::validate_payload( $payload );
		if ( is_wp_error( $access ) ) {
			return $access;
		}

		return self::redirect_response( $access );
	}

	/**
	 * @return string|\WP_Error
	 */
	public static function resolve_redirect( string $admin_path ) {
		$admin_path = trim( $admin_path );
		if ( '' === $admin_path ) {
			return admin_url();
		}

		if (
			str_contains( $admin_path, "\r" )
			|| str_contains( $admin_path, "\n" )
			|| 1 === preg_match( '#^[a-z][a-z0-9+.-]*:#i', $admin_path )
			|| str_starts_with( $admin_path, '//' )
		) {
			return new \WP_Error(
				'invalid_admin_access_redirect',
				'Redirect path must be relative to wp-admin, not an absolute URL.'
			);
		}

		$admin_path = ltrim( $admin_path, '/' );
		if ( str_starts_with( $admin_path, 'wp-admin/' ) ) {
			$admin_path = substr( $admin_path, strlen( 'wp-admin/' ) );
		}

		return admin_url( $admin_path );
	}

	/**
	 * @param array<string, mixed> $payload
	 * @return array{user_id: int, redirect_url: string, expires_at: int, session_expires_in: int}|\WP_Error
	 */
	private static function validate_payload( array $payload ) {
		$expires_at = (int) ( $payload['expires_at'] ?? 0 );
		if ( $expires_at < time() ) {
			return new \WP_Error( 'invalid_admin_access_token', 'Invalid or expired admin access token.', array( 'status' => 401 ) );
		}

		$user_id = (int) ( $payload['user_id'] ?? 0 );
		$user    = get_user_by( 'id', $user_id );
		if ( ! $user instanceof \WP_User || ! AbilityHelpers::user_can_manage( $user ) ) {
			return new \WP_Error( 'invalid_admin_access_token', 'Invalid or expired admin access token.', array( 'status' => 401 ) );
		}

		$redirect_url = $payload['redirect_url'] ?? '';
		if ( ! is_string( $redirect_url ) || ! str_starts_with( $redirect_url, admin_url() ) ) {
			return new \WP_Error( 'invalid_admin_access_token', 'Invalid or expired admin access token.', array( 'status' => 401 ) );
		}

		return array(
			'user_id'            => $user_id,
			'redirect_url'       => $redirect_url,
			'expires_at'         => $expires_at,
			'session_expires_in' => max( 60, min( 3600, (int) ( $payload['session_expires_in'] ?? 1800 ) ) ),
		);
	}

	/**
	 * @param array{user_id: int, redirect_url: string, expires_at: int, session_expires_in: int} $access
	 */
	private static function redirect_response( array $access ): \WP_REST_Response {
		$session_expires_in = $access['session_expires_in'];
		$expire_session     = static fn( int $length ): int => $session_expires_in;

		add_filter( 'auth_cookie_expiration', $expire_session );
		try {
			wp_set_current_user( $access['user_id'] );
			wp_set_auth_cookie( $access['user_id'], false, is_ssl() );
		} finally {
			remove_filter( 'auth_cookie_expiration', $expire_session );
		}

		$response = new \WP_REST_Response( null, 302 );
		$response->header( 'Location', $access['redirect_url'] );
		self::no_store_headers( $response );
		$response->header( 'Referrer-Policy', 'no-referrer' );

		return $response;
	}

	private static function no_store_headers( \WP_REST_Response $response ): void {
		$response->header( 'Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0' );
		$response->header( 'Pragma', 'no-cache' );
	}

	private static function token_key( string $token ): string {
		return 'layrshift_admin_access_' . hash_hmac( 'sha256', $token, wp_salt( 'auth' ) );
	}

	private static function login_nonce_key( string $nonce ): string {
		return 'layrshift_admin_access_login_' . hash_hmac( 'sha256', $nonce, wp_salt( 'auth' ) );
	}

	private static function nonce_hash( string $nonce ): string {
		return hash_hmac( 'sha256', $nonce, wp_salt( 'nonce' ) . '|layrshift-admin-access' );
	}
}
