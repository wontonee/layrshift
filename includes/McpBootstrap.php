<?php
/**
 * Early MCP / Abilities API bootstrap.
 *
 * The bundled mcp-adapter registers its category and abilities lazily inside
 * McpAdapter::init(). When another plugin calls wp_get_abilities() before that
 * runs, WordPress boots the Abilities API without mcp-adapter hooks and emits
 * _doing_it_wrong notices. LayrShift registers the shared primitives early and
 * disables the adapter default server (LayrShift registers its own MCP server).
 *
 * @package LayrShift
 */

declare( strict_types=1 );

namespace LayrShift;

use WP\MCP\Abilities\DiscoverAbilitiesAbility;
use WP\MCP\Abilities\ExecuteAbilityAbility;
use WP\MCP\Abilities\GetAbilityInfoAbility;

final class McpBootstrap {

	public static function init(): void {
		add_filter( 'mcp_adapter_create_default_server', '__return_false' );

		add_action( 'wp_abilities_api_categories_init', array( self::class, 'register_categories' ), 1 );
		add_action( 'wp_abilities_api_init', array( self::class, 'register_mcp_adapter_abilities' ), 1 );
	}

	public static function register_categories(): void {
		if ( ! wp_has_ability_category( 'mcp-adapter' ) ) {
			wp_register_ability_category(
				'mcp-adapter',
				array(
					'label'       => __( 'MCP Adapter', 'layrshift' ),
					'description' => __( 'Meta-abilities for MCP protocol bridging.', 'layrshift' ),
				)
			);
		}

		if ( ! wp_has_ability_category( 'skill' ) ) {
			wp_register_ability_category(
				'skill',
				array(
					'label'       => __( 'Skills', 'layrshift' ),
					'description' => __( 'Manage and load agent skills.', 'layrshift' ),
				)
			);
		}

		$extra_categories = array(
			'code-execution' => array(
				'label'       => __( 'Code Execution', 'layrshift' ),
				'description' => __( 'Abilities that execute code on the WordPress server.', 'layrshift' ),
			),
			'filesystem'     => array(
				'label'       => __( 'Filesystem', 'layrshift' ),
				'description' => __( 'Server filesystem operations.', 'layrshift' ),
			),
			'admin-access'   => array(
				'label'       => __( 'Admin Access', 'layrshift' ),
				'description' => __( 'Temporary browser access to WordPress admin.', 'layrshift' ),
			),
			'gutenberg'      => array(
				'label'       => __( 'Gutenberg', 'layrshift' ),
				'description' => __( 'Gutenberg content abilities and the Block Editor Queue for static blocks that need browser finalization.', 'layrshift' ),
			),
		);

		foreach ( $extra_categories as $slug => $config ) {
			if ( wp_has_ability_category( $slug ) ) {
				continue;
			}
			wp_register_ability_category( $slug, $config );
		}
	}

	public static function register_mcp_adapter_abilities(): void {
		if ( ! wp_has_ability( 'mcp-adapter/discover-abilities' ) ) {
			DiscoverAbilitiesAbility::register();
		}

		if ( ! wp_has_ability( 'mcp-adapter/get-ability-info' ) ) {
			GetAbilityInfoAbility::register();
		}

		if ( ! wp_has_ability( 'mcp-adapter/execute-ability' ) ) {
			ExecuteAbilityAbility::register();
		}
	}
}
