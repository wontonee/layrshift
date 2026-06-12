<?php
/**
 * LayrShift replacement for mcp-adapter/discover-abilities.
 *
 * @package LayrShift
 */

declare( strict_types=1 );

namespace LayrShift\Abilities;

use LayrShift\AbilityCategories;
use LayrShift\Auth;
use LayrShift\Instructions;
use LayrShift\Plugin;

/**
 * Injects LayrShift instructions into discover-abilities responses.
 */
final class DiscoverAbilities {

	public static function register(): void {
		if ( ! Plugin::meets_requirements() ) {
			return;
		}

		$existing = wp_get_ability( 'mcp-adapter/discover-abilities' );
		if ( null !== $existing ) {
			wp_unregister_ability( 'mcp-adapter/discover-abilities' );
		}

		if ( null !== wp_get_ability( 'mcp-adapter/discover-abilities' ) ) {
			return;
		}

		wp_register_ability(
			'mcp-adapter/discover-abilities',
			array(
				'label'               => __( 'Discover Abilities', 'layrshift' ),
				'description'         => __( 'Discover all available WordPress abilities, plus LayrShift environment instructions.', 'layrshift' ),
				'category'            => AbilityCategories::MCP_ADAPTER,
				'execute_callback'    => array( self::class, 'execute' ),
				'permission_callback' => array( self::class, 'check_permission' ),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'layrshift_instructions' => array(
							'type'        => 'string',
							'description' => __( 'LayrShift environment and usage guidance for the agent.', 'layrshift' ),
						),
						'abilities'              => array(
							'type'  => 'array',
							'items' => array(
								'type'       => 'object',
								'properties' => array(
									'name'        => array( 'type' => 'string' ),
									'label'       => array( 'type' => 'string' ),
									'description' => array( 'type' => 'string' ),
								),
								'required'   => array( 'name', 'label', 'description' ),
							),
						),
					),
					'required'   => array( 'layrshift_instructions', 'abilities' ),
				),
				'meta'                => array(
					'annotations' => array(
						'readOnly'    => true,
						'destructive' => false,
						'idempotent'  => true,
					),
					'mcp'         => array(
						'public' => true,
						'type'   => 'tool',
					),
				),
			)
		);
	}

	/**
	 * @return bool|\WP_Error
	 */
	public static function check_permission(): bool|\WP_Error {
		if ( ! Auth::check_ability_permission() ) {
			return new \WP_Error( 'authentication_required', __( 'User must be authenticated with LayrShift abilities enabled.', 'layrshift' ) );
		}

		return true;
	}

	/**
	 * @return array{layrshift_instructions: string, abilities: list<array{name: string, label: string, description: string}>}
	 */
	public static function execute(): array {
		$ability_list = array();

		foreach ( wp_get_abilities() as $ability ) {
			$meta = $ability->get_meta();
			if ( empty( $meta['mcp']['public'] ) ) {
				continue;
			}
			if ( ( $meta['mcp']['type'] ?? 'tool' ) !== 'tool' ) {
				continue;
			}

			$ability_list[] = array(
				'name'        => $ability->get_name(),
				'label'       => $ability->get_label(),
				'description' => $ability->get_description(),
			);
		}

		$instructions = '';
		if ( Auth::current_user_allowed() ) {
			$instructions = Instructions::build();
		}

		return array(
			'layrshift_instructions' => $instructions,
			'abilities'              => $ability_list,
		);
	}
}
