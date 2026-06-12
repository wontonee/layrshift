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
		McpToolNames::init();

		add_action( 'wp_abilities_api_categories_init', array( self::class, 'register_categories' ), 1 );
		add_action( 'wp_abilities_api_init', array( self::class, 'register_mcp_adapter_abilities' ), 1 );

		add_filter( 'mcp_adapter_discover_abilities_capability', array( Auth::class, 'mcp_adapter_capability' ) );
		add_filter( 'mcp_adapter_execute_ability_capability', array( Auth::class, 'mcp_adapter_capability' ) );
		add_filter( 'mcp_adapter_get_ability_info_capability', array( Auth::class, 'mcp_adapter_capability' ) );
		add_filter( 'mcp_adapter_default_transport_permission_user_capability', array( Auth::class, 'mcp_adapter_capability' ) );
	}

	public static function register_categories(): void {
		if ( ! wp_has_ability_category( AbilityCategories::MCP_ADAPTER ) ) {
			wp_register_ability_category(
				AbilityCategories::MCP_ADAPTER,
				array(
					'label'       => __( 'MCP Adapter', 'layrshift' ),
					'description' => __( 'Meta-abilities for MCP protocol bridging.', 'layrshift' ),
				)
			);
		}

		if ( ! wp_has_ability_category( AbilityCategories::SKILL ) ) {
			wp_register_ability_category(
				AbilityCategories::SKILL,
				array(
					'label'       => __( 'Skills', 'layrshift' ),
					'description' => __( 'Manage and load agent skills.', 'layrshift' ),
				)
			);
		}

		$extra_categories = array(
			AbilityCategories::CODE_EXECUTION => array(
				'label'       => __( 'Code Execution', 'layrshift' ),
				'description' => __( 'Abilities that execute code on the WordPress server.', 'layrshift' ),
			),
			AbilityCategories::FILESYSTEM     => array(
				'label'       => __( 'Filesystem', 'layrshift' ),
				'description' => __( 'Server filesystem operations.', 'layrshift' ),
			),
			AbilityCategories::ADMIN_ACCESS   => array(
				'label'       => __( 'Admin Access', 'layrshift' ),
				'description' => __( 'Temporary browser access to WordPress admin.', 'layrshift' ),
			),
			AbilityCategories::GUTENBERG      => array(
				'label'       => __( 'Gutenberg', 'layrshift' ),
				'description' => __( 'Gutenberg content abilities and the Block Editor Queue for static blocks that need browser finalization.', 'layrshift' ),
			),
			AbilityCategories::ELEMENTOR      => array(
				'label'       => __( 'Elementor', 'layrshift' ),
				'description' => __( 'Read and save Elementor documents via the Elementor API.', 'layrshift' ),
			),
			AbilityCategories::YOAST          => array(
				'label'       => __( 'Yoast SEO', 'layrshift' ),
				'description' => __( 'Read and update Yoast SEO metadata and site settings.', 'layrshift' ),
			),
			AbilityCategories::SMUSH          => array(
				'label'       => __( 'Smush', 'layrshift' ),
				'description' => __( 'Image optimization stats and bulk smush operations.', 'layrshift' ),
			),
			AbilityCategories::VAULTSHIFT     => array(
				'label'       => __( 'VaultShift', 'layrshift' ),
				'description' => __( 'Security status, scans, and activity log for VaultShift.', 'layrshift' ),
			),
			AbilityCategories::BLOGIBOT       => array(
				'label'       => __( 'BlogiBot', 'layrshift' ),
				'description' => __( 'BlogiBot content status, posts, and settings.', 'layrshift' ),
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
