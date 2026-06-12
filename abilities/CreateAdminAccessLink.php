<?php
/**
 * Create admin access link ability.
 *
 * @package LayrShift
 */

declare( strict_types=1 );

namespace LayrShift\Abilities;

use LayrShift\AbilityCategories;
use LayrShift\AbilityHelpers;
use LayrShift\Api\AdminAccessEndpoint;
use LayrShift\Auth;

final class CreateAdminAccessLink {

	use AbilityTrait;

	public static function register(): void {
		if ( wp_has_ability( 'layrshift/create-admin-access-link' ) ) {
			return;
		}

		wp_register_ability(
			'layrshift/create-admin-access-link',
			array(
				'label'               => __( 'Create Admin Access Link', 'layrshift' ),
				'description'         => __( 'Creates a temporary, one-time WordPress admin access exchange for browser automation tools.', 'layrshift' ),
				'category'            => AbilityCategories::ADMIN_ACCESS,
				'execute_callback'    => array( self::class, 'execute' ),
				'permission_callback' => array( Auth::class, 'check_ability_permission' ),
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'expires_in'         => array( 'type' => 'integer', 'default' => 300, 'minimum' => 30, 'maximum' => 600 ),
						'session_expires_in' => array( 'type' => 'integer', 'default' => 1800, 'minimum' => 60, 'maximum' => 3600 ),
						'admin_path'         => array( 'type' => 'string', 'default' => '' ),
					),
				),
				'meta'                => array(
					'mcp' => array( 'public' => true, 'type' => 'tool' ),
				),
			)
		);
	}

	/**
	 * @param array<string, mixed> $input
	 * @return array<string, mixed>|\WP_Error
	 */
	public static function execute( array $input = array() ) {
		return self::run_logged(
			'layrshift/create-admin-access-link',
			$input,
			static function () use ( $input ) {
				$user_id = get_current_user_id();
				if ( $user_id <= 0 || ! AbilityHelpers::current_user_can_manage() ) {
					return new \WP_Error( 'admin_access_forbidden', 'Only administrators can create admin access links.' );
				}

				$expires_in         = max( 30, min( 600, (int) ( $input['expires_in'] ?? 300 ) ) );
				$session_expires_in = max( 60, min( 3600, (int) ( $input['session_expires_in'] ?? 1800 ) ) );
				$admin_path         = (string) ( $input['admin_path'] ?? '' );
				$redirect_url       = AdminAccessEndpoint::resolve_redirect( $admin_path );
				if ( is_wp_error( $redirect_url ) ) {
					return $redirect_url;
				}

				$access = AdminAccessEndpoint::create_token( $user_id, $expires_in, $session_expires_in, $admin_path );
				if ( is_wp_error( $access ) ) {
					return $access;
				}

				$exchange_url = rest_url( 'layrshift/v1/admin-access' );
				$token_header = 'X-LayrShift-Admin-Access-Token';
				$nonce_header = 'X-LayrShift-Admin-Access-Nonce';

				return array(
					'exchange_url'       => $exchange_url,
					'exchange_method'    => 'POST',
					'access_token'       => $access['token'],
					'token_header'       => $token_header,
					'access_nonce'       => $access['nonce'],
					'nonce_header'       => $nonce_header,
					'expires_at'         => $access['expires_at'],
					'session_expires_in' => $session_expires_in,
					'redirect_url'       => $redirect_url,
					'one_time'           => true,
					'curl_example'       => sprintf(
						'curl -s -X POST -H "%s: $access_token" -H "%s: $access_nonce" %s',
						$token_header,
						$nonce_header,
						escapeshellarg( $exchange_url )
					),
				);
			}
		);
	}
}
