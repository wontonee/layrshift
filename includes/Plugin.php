<?php
/**
 * Main plugin bootstrap.
 *
 * @package LayrShift
 */

declare( strict_types=1 );

namespace LayrShift;

use LayrShift\Abilities\DiscoverAbilities;
use LayrShift\AbilityPolicy;
use LayrShift\Admin\Admin;
use LayrShift\Admin\AdminBar;
use LayrShift\Api\AdminAccessEndpoint;
use LayrShift\Api\ServerFactory;
use LayrShift\Api\TemplateStudioEndpoint;
use LayrShift\Api\UploadEndpoint;
use LayrShift\Gutenberg\Loader as GutenbergLoader;
use LayrShift\Skills\Bootstrap as SkillsBootstrap;

/**
 * Plugin singleton.
 */
final class Plugin {

	private static ?self $instance = null;

	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		Upgrade::init();
		CrashRecovery::init();
		Sandbox::init();
		McpBootstrap::init();
		SkillsBootstrap::init();
		AbilityPolicy::init();
		GutenbergLoader::init_runtime();

		add_action( 'init', array( $this, 'on_init' ) );
		add_action( 'rest_api_init', array( $this, 'bootstrap_mcp_adapter' ), 14 );
		add_action( 'wp_abilities_api_init', array( AbilitiesRegistry::class, 'register' ) );
		add_action( 'wp_abilities_api_init', array( DiscoverAbilities::class, 'register' ), 100 );
		add_action( 'mcp_adapter_init', array( ServerFactory::class, 'register' ) );
		add_action( 'rest_api_init', array( UploadEndpoint::class, 'register_routes' ) );
		add_action( 'rest_api_init', array( AdminAccessEndpoint::class, 'register_routes' ) );
		add_action( 'rest_api_init', array( TemplateStudioEndpoint::class, 'register_routes' ) );

		if ( is_admin() ) {
			Admin::init();
		}

		AdminBar::init();
	}

	/**
	 * Initialize MCP adapter on REST bootstrap (matches mcp-adapter timing).
	 */
	public function bootstrap_mcp_adapter(): void {
		if ( ! class_exists( \WP\MCP\Core\McpAdapter::class ) ) {
			return;
		}

		\WP\MCP\Core\McpAdapter::instance()->init();
	}

	public function on_init(): void {
		load_plugin_textdomain( 'layrshift', false, dirname( plugin_basename( LAYRSHIFT_FILE ) ) . '/languages' );
	}

	public static function activate(): void {
		self::migrate_legacy_options();

		$defaults = array(
			'enabled'                 => false,
			'allowed_user_ids'        => array(),
			'exec_time_limit'         => 30,
			'https_enforcement'       => true,
			'restrict_core_deletion'  => true,
			'risk_acknowledged'       => false,
		);

		if ( false === get_option( 'layrshift_settings' ) ) {
			add_option( 'layrshift_settings', $defaults );
		}

		if ( false === get_option( 'layrshift_pro_settings' ) ) {
			add_option(
				'layrshift_pro_settings',
				array(
					'enabled'        => false,
					'gemini_api_key' => '',
					'gemini_model'   => 'gemini-2.0-flash',
					'default_editor' => 'auto',
				)
			);
		}

		Sandbox::ensure_directory();
	}

	/**
	 * Migrate options from the former ForgePress plugin slug.
	 */
	private static function migrate_legacy_options(): void {
		$legacy_map = array(
			'forgepress_settings'     => 'layrshift_settings',
			'forgepress_pro_settings' => 'layrshift_pro_settings',
			'forgepress_ability_log'  => 'layrshift_ability_log',
		);

		foreach ( $legacy_map as $legacy_key => $new_key ) {
			$legacy_value = get_option( $legacy_key, null );
			if ( null === $legacy_value ) {
				continue;
			}

			if ( false === get_option( $new_key, false ) ) {
				update_option( $new_key, $legacy_value );
			}

			delete_option( $legacy_key );
		}
	}

	public static function deactivate(): void {
		$settings               = get_option( 'layrshift_settings', array() );
		$settings['enabled']    = false;
		$settings['risk_acknowledged'] = false;
		update_option( 'layrshift_settings', $settings );
		GutenbergLoader::unschedule_cleanup();
	}

	/**
	 * @return array<string, mixed>
	 */
	public static function get_settings(): array {
		$defaults = array(
			'enabled'                 => false,
			'allowed_user_ids'        => array(),
			'exec_time_limit'         => 30,
			'https_enforcement'       => true,
			'restrict_core_deletion'  => true,
			'risk_acknowledged'       => false,
		);

		$settings = get_option( 'layrshift_settings', array() );
		if ( ! is_array( $settings ) ) {
			$settings = array();
		}

		return array_merge( $defaults, $settings );
	}

	/**
	 * @param array<string, mixed> $settings Settings to save.
	 */
	public static function save_settings( array $settings ): void {
		update_option( 'layrshift_settings', $settings );
	}

	public static function is_abilities_enabled(): bool {
		$settings = self::get_settings();
		return ! empty( $settings['enabled'] ) && ! empty( $settings['risk_acknowledged'] );
	}

	public static function enable_abilities(): bool {
		if ( null !== self::get_dependency_error() ) {
			return false;
		}

		$settings                        = self::get_settings();
		$settings['enabled']             = true;
		$settings['risk_acknowledged']   = true;
		self::save_settings( $settings );

		return true;
	}

	public static function disable_abilities(): bool {
		$settings              = self::get_settings();
		$settings['enabled']   = false;
		self::save_settings( $settings );

		return true;
	}

	public static function get_dependency_error(): ?string {
		global $wp_version;

		if ( version_compare( $wp_version, '6.9', '<' ) ) {
			return __( 'WordPress 6.9 or newer is required.', 'layrshift' );
		}

		if ( ! function_exists( 'wp_register_ability' ) ) {
			return __( 'WordPress Abilities API is not available.', 'layrshift' );
		}

		if ( ! class_exists( \WP\MCP\Core\McpAdapter::class ) ) {
			return __( 'MCP Adapter is not available.', 'layrshift' );
		}

		return null;
	}

	/**
	 * @return array{
	 *     state: string,
	 *     configured: bool,
	 *     active: bool,
	 *     can_enable: bool,
	 *     dependency_error: string|null
	 * }
	 */
	public static function get_abilities_status(): array {
		$configured      = self::is_abilities_enabled();
		$dependency_error = self::get_dependency_error();
		$https_blocked   = Auth::is_https_required_and_missing();

		if ( $https_blocked && null === $dependency_error ) {
			$dependency_error = __( 'HTTPS is required but this site is not served over SSL.', 'layrshift' );
		}

		$runtime_ok = null === $dependency_error && ! $https_blocked;
		$active     = $configured && $runtime_ok;
		$error      = $configured && ! $runtime_ok;

		$state = 'off';
		if ( $active ) {
			$state = 'on';
		} elseif ( $error ) {
			$state = 'error';
		}

		return array(
			'state'            => $state,
			'configured'       => $configured,
			'active'           => $active,
			'can_enable'       => $configured || null === self::get_dependency_error(),
			'dependency_error' => $dependency_error,
		);
	}

	public static function meets_requirements(): bool {
		return null === self::get_dependency_error();
	}
}
