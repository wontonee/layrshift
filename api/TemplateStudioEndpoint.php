<?php
/**
 * Template Studio REST API.
 *
 * @package LayrShift
 */

declare( strict_types=1 );

namespace LayrShift\Api;

use LayrShift\Pro\EditorDetector;
use LayrShift\Pro\ProSettings;
use LayrShift\Pro\TemplateGenerator;

final class TemplateStudioEndpoint {

	public static function register_routes(): void {
		register_rest_route(
			'layrshift/v1',
			'/templates/editors',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( self::class, 'list_editors' ),
				'permission_callback' => array( self::class, 'can_manage_templates' ),
			)
		);

		register_rest_route(
			'layrshift/v1',
			'/templates/settings',
			array(
				'methods'             => \WP_REST_Server::EDITABLE,
				'callback'            => array( self::class, 'save_settings' ),
				'permission_callback' => array( self::class, 'can_manage_templates' ),
				'args'                => array(
					'enabled'        => array( 'type' => 'boolean' ),
					'gemini_api_key' => array( 'type' => 'string' ),
					'gemini_model'   => array( 'type' => 'string' ),
					'default_editor' => array( 'type' => 'string' ),
				),
			)
		);

		register_rest_route(
			'layrshift/v1',
			'/templates/generate',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( self::class, 'generate' ),
				'permission_callback' => array( self::class, 'can_manage_templates' ),
				'args'                => array(
					'prompt' => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_textarea_field',
					),
					'editor' => array(
						'type'              => 'string',
						'default'           => 'auto',
						'sanitize_callback' => 'sanitize_key',
					),
					'title'  => array(
						'type'              => 'string',
						'default'           => '',
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);

		register_rest_route(
			'layrshift/v1',
			'/templates/create',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( self::class, 'create' ),
				'permission_callback' => array( self::class, 'can_manage_templates' ),
				'args'                => array(
					'title'   => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'content' => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => array( self::class, 'preserve_block_markup' ),
					),
					'editor'  => array(
						'type'              => 'string',
						'default'           => 'gutenberg',
						'sanitize_callback' => 'sanitize_key',
					),
				),
			)
		);
	}

	public static function can_manage_templates(): bool {
		return current_user_can( 'manage_options' ) && current_user_can( 'edit_posts' );
	}

	/**
	 * @return \WP_REST_Response
	 */
	public static function list_editors( \WP_REST_Request $request ) {
		unset( $request );

		$settings = ProSettings::get();

		return rest_ensure_response(
			array(
				'editors'         => EditorDetector::list_editors(),
				'default_editor'  => (string) $settings['default_editor'],
				'configured'      => ProSettings::is_configured(),
				'elementor_ready' => false,
			)
		);
	}

	/**
	 * @return \WP_REST_Response
	 */
	public static function save_settings( \WP_REST_Request $request ) {
		$current = ProSettings::get();
		$payload = array(
			'enabled'        => null !== $request->get_param( 'enabled' ) ? (bool) $request->get_param( 'enabled' ) : (bool) $current['enabled'],
			'gemini_api_key' => null !== $request->get_param( 'gemini_api_key' ) ? (string) $request->get_param( 'gemini_api_key' ) : (string) $current['gemini_api_key'],
			'gemini_model'   => null !== $request->get_param( 'gemini_model' ) ? (string) $request->get_param( 'gemini_model' ) : (string) $current['gemini_model'],
			'default_editor' => null !== $request->get_param( 'default_editor' ) ? (string) $request->get_param( 'default_editor' ) : (string) $current['default_editor'],
		);

		ProSettings::save( $payload );

		return rest_ensure_response(
			array(
				'success'    => true,
				'configured' => ProSettings::is_configured(),
				'has_api_key' => ProSettings::has_api_key(),
				'model'      => (string) ProSettings::get()['gemini_model'],
			)
		);
	}

	/**
	 * @return \WP_REST_Response|\WP_Error
	 */
	public static function generate( \WP_REST_Request $request ) {
		$result = TemplateGenerator::generate(
			(string) $request->get_param( 'prompt' ),
			(string) $request->get_param( 'editor' ),
			(string) $request->get_param( 'title' )
		);

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return rest_ensure_response( $result );
	}

	/**
	 * @return \WP_REST_Response|\WP_Error
	 */
	public static function create( \WP_REST_Request $request ) {
		$result = TemplateGenerator::create_draft(
			(string) $request->get_param( 'title' ),
			(string) $request->get_param( 'content' ),
			(string) $request->get_param( 'editor' )
		);

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return rest_ensure_response( $result );
	}

	/**
	 * Keep Gutenberg block comments intact; only normalize UTF-8 for admin POST bodies.
	 */
	public static function preserve_block_markup( string $value ): string {
		return wp_check_invalid_utf8( wp_unslash( $value ), true );
	}
}
