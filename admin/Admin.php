<?php
/**
 * Admin UI bootstrap.
 *
 * @package LayrShift
 */

declare( strict_types=1 );

namespace LayrShift\Admin;

use LayrShift\Auth;
use LayrShift\Logger;
use LayrShift\Plugin;
use LayrShift\Pro\ProSettings;
use LayrShift\Sandbox;

final class Admin {

	public const APP_PAGE = 'layrshift-app';

	public const LEGACY_APP_PAGE = 'layrshift';

	private const ALLOWED_TABS = array( 'mcp', 'settings' );

	public static function init(): void {
		McpConnect::init();
		AbilitiesHub::init();
		add_action( 'admin_menu', array( self::class, 'register_menus' ), 5 );
		add_action( 'admin_init', array( self::class, 'register_settings' ) );
		add_action( 'admin_init', array( self::class, 'redirect_legacy_slugs' ), 0 );
		add_action( 'admin_enqueue_scripts', array( self::class, 'enqueue_assets' ) );
		add_filter( 'admin_body_class', array( self::class, 'admin_body_class' ) );
		add_action( 'admin_post_layrshift_sandbox_action', array( self::class, 'handle_sandbox_action' ) );
		add_action( 'admin_post_layrshift_clear_log', array( self::class, 'handle_clear_log' ) );
		add_action( 'admin_notices', array( self::class, 'requirements_notice' ) );
	}

	public static function admin_body_class( string $classes ): string {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( $screen && false !== strpos( (string) $screen->id, 'layrshift' ) ) {
			$classes .= ' layrshift-admin';
			$tab = self::get_active_tab();
			if ( self::is_app_screen( $screen ) ) {
				$classes .= ' layrshift-tab-' . sanitize_html_class( $tab );
			}
		}

		return $classes;
	}

	public static function register_menus(): void {
		add_menu_page(
			__( 'LayrShift', 'layrshift' ),
			__( 'LayrShift', 'layrshift' ),
			'manage_options',
			self::APP_PAGE,
			array( self::class, 'render_app' ),
			'dashicons-layout',
			81
		);

		add_submenu_page(
			self::APP_PAGE,
			__( 'Configuration', 'layrshift' ),
			__( 'Configuration', 'layrshift' ),
			'manage_options',
			self::APP_PAGE,
			array( self::class, 'render_app' )
		);

		add_submenu_page(
			self::APP_PAGE,
			__( 'Agent Sandbox', 'layrshift' ),
			__( 'Agent Sandbox', 'layrshift' ),
			'manage_options',
			'layrshift-sandbox',
			array( self::class, 'render_sandbox' )
		);

		add_submenu_page(
			self::APP_PAGE,
			__( 'Activity Log', 'layrshift' ),
			__( 'Activity Log', 'layrshift' ),
			'manage_options',
			'layrshift-log',
			array( self::class, 'render_log' )
		);

		// Back-compat: old bookmarks and links using page=layrshift still resolve.
		add_submenu_page(
			self::APP_PAGE,
			__( 'LayrShift', 'layrshift' ),
			'',
			'manage_options',
			self::LEGACY_APP_PAGE,
			array( self::class, 'render_app' )
		);
		remove_submenu_page( self::APP_PAGE, self::LEGACY_APP_PAGE );
	}

	public static function redirect_legacy_slugs(): void {
		if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		global $pagenow;

		$page = sanitize_key( (string) ( $_GET['page'] ?? '' ) );

		$legacy_map = array(
			self::LEGACY_APP_PAGE       => self::APP_PAGE,
			'layrshift-template-studio' => 'mcp',
			'layrshift-connect'         => 'mcp',
		);

		if ( isset( $legacy_map[ $page ] ) ) {
			$target = $legacy_map[ $page ];
			if ( self::LEGACY_APP_PAGE === $page ) {
				$tab = isset( $_GET['tab'] ) ? sanitize_key( (string) $_GET['tab'] ) : '';
				wp_safe_redirect( self::app_url( $tab ) );
				exit;
			}
			wp_safe_redirect( self::app_url( $target ) );
			exit;
		}

		if ( self::APP_PAGE === $page && isset( $_GET['tab'] ) ) {
			$tab = sanitize_key( (string) $_GET['tab'] );
			if ( in_array( $tab, array( 'generate', 'preview' ), true ) ) {
				wp_safe_redirect( admin_url( 'admin.php?page=' . self::APP_PAGE . '&tab=mcp' ) );
				exit;
			}
		}

		if ( 'options-general.php' === $pagenow && 'layrshift-settings' === $page ) {
			wp_safe_redirect( admin_url( 'admin.php?page=' . self::APP_PAGE . '&tab=settings' ) );
			exit;
		}
	}

	public static function is_app_page( string $page ): bool {
		return in_array( $page, array( self::APP_PAGE, self::LEGACY_APP_PAGE ), true );
	}

	public static function app_url( string $tab = '' ): string {
		$args = array( 'page' => self::APP_PAGE );
		$tab  = sanitize_key( $tab );
		if ( '' !== $tab && in_array( $tab, self::ALLOWED_TABS, true ) ) {
			$args['tab'] = $tab;
		}

		return add_query_arg( $args, admin_url( 'admin.php' ) );
	}

	public static function get_active_tab(): string {
		$tab = sanitize_key( (string) ( $_GET['tab'] ?? 'mcp' ) );

		return in_array( $tab, self::ALLOWED_TABS, true ) ? $tab : 'mcp';
	}

	private static function is_app_screen( \WP_Screen $screen ): bool {
		$id = (string) $screen->id;

		return false !== strpos( $id, self::APP_PAGE )
			|| str_ends_with( $id, '_page_' . self::LEGACY_APP_PAGE );
	}

	public static function register_settings(): void {
		register_setting(
			'layrshift_settings_group',
			'layrshift_settings',
			array(
				'type'              => 'array',
				'sanitize_callback' => array( self::class, 'sanitize_settings' ),
				'default'           => Plugin::get_settings(),
			)
		);
	}

	/**
	 * @param array<string, mixed>|mixed $input Raw settings.
	 * @return array<string, mixed>
	 */
	public static function sanitize_settings( $input ): array {
		$current = Plugin::get_settings();
		if ( ! is_array( $input ) ) {
			return $current;
		}

		$allowed_users = array();
		if ( ! empty( $input['allowed_user_ids'] ) && is_array( $input['allowed_user_ids'] ) ) {
			$allowed_users = array_map( 'absint', $input['allowed_user_ids'] );
		}

		$saved = array(
			'enabled'                => ! empty( $input['enabled'] ),
			'allowed_user_ids'       => $allowed_users,
			'exec_time_limit'        => max( 5, min( 120, (int) ( $input['exec_time_limit'] ?? 30 ) ) ),
			'https_enforcement'      => ! empty( $input['https_enforcement'] ),
			'restrict_core_deletion' => ! empty( $input['restrict_core_deletion'] ),
			'risk_acknowledged'      => ! empty( $input['risk_acknowledged'] ),
		);

		if ( isset( $input['pro'] ) && is_array( $input['pro'] ) ) {
			ProSettings::save( $input['pro'] );
		}

		return $saved;
	}

	public static function enqueue_assets( string $hook ): void {
		if ( false === strpos( $hook, 'layrshift' ) ) {
			return;
		}

		wp_enqueue_style(
			'layrshift-admin',
			LAYRSHIFT_URL . 'admin/assets/admin.css',
			array(),
			LAYRSHIFT_VERSION
		);

		wp_enqueue_script(
			'layrshift-admin',
			LAYRSHIFT_URL . 'admin/assets/admin.js',
			array(),
			LAYRSHIFT_VERSION,
			true
		);

		if ( self::is_app_admin_hook( $hook ) && 'mcp' === self::get_active_tab() ) {
			wp_enqueue_script(
				'layrshift-mcp-connect',
				LAYRSHIFT_URL . 'admin/assets/mcp-connect.js',
				array(),
				LAYRSHIFT_VERSION,
				true
			);
		}
	}

	public static function requirements_notice(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$errors = Auth::get_requirement_errors();
		if ( empty( $errors ) ) {
			return;
		}

		echo '<div class="notice notice-error"><p><strong>LayrShift:</strong> ';
		echo esc_html( implode( ' ', $errors ) );
		echo '</p></div>';
	}

	private static function is_app_admin_hook( string $hook ): bool {
		return false !== strpos( $hook, self::APP_PAGE )
			|| str_ends_with( $hook, '_page_' . self::LEGACY_APP_PAGE );
	}

	public static function render_app(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$active_tab   = self::get_active_tab();
		$settings     = Plugin::get_settings();
		$pro_settings = ProSettings::get();
		$admins       = get_users( array( 'role' => 'administrator', 'fields' => array( 'ID', 'display_name', 'user_login' ) ) );
		$errors       = Auth::get_requirement_errors();
		$mcp_url      = rest_url( 'layrshift/v1/mcp' );
		$mcp_connect  = McpConnect::get_tab_context( $mcp_url );
		$setup_prompt = (string) ( $mcp_connect['setup_prompt'] ?? '' );

		include LAYRSHIFT_PATH . 'admin/views/app.php';
	}

	public static function render_sandbox(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$files = Sandbox::list_files();
		include LAYRSHIFT_PATH . 'admin/views/sandbox.php';
	}

	public static function render_log(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$entries = Logger::get_entries();
		include LAYRSHIFT_PATH . 'admin/views/log.php';
	}

	public static function handle_sandbox_action(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized', 'layrshift' ) );
		}

		check_admin_referer( 'layrshift_sandbox_action' );

		$action   = sanitize_key( (string) ( $_POST['layrshift_action'] ?? '' ) );
		$filename = isset( $_POST['filename'] ) ? sanitize_file_name( wp_unslash( (string) $_POST['filename'] ) ) : '';

		switch ( $action ) {
			case 'disable':
				Sandbox::disable_file( $filename );
				break;
			case 'enable':
				Sandbox::enable_file( $filename );
				break;
			case 'delete':
				$path = Sandbox::get_directory() . '/' . $filename;
				if ( file_exists( $path ) ) {
					unlink( $path );
				}
				if ( file_exists( $path . '.disabled' ) ) {
					unlink( $path . '.disabled' );
				}
				Sandbox::unregister_file( $filename );
				break;
			case 'exit_safe_mode':
				Sandbox::disable_safe_mode();
				break;
			case 'disable_all':
				foreach ( Sandbox::list_files() as $file ) {
					if ( 'active' === ( $file['status'] ?? '' ) ) {
						Sandbox::disable_file( (string) $file['filename'] );
					}
				}
				break;
			case 'enable_all':
				foreach ( Sandbox::list_files() as $file ) {
					if ( 'disabled' === ( $file['status'] ?? '' ) ) {
						Sandbox::enable_file( (string) $file['filename'] );
					}
				}
				break;
			case 'delete_all':
				foreach ( Sandbox::list_files() as $file ) {
					$path = Sandbox::get_directory() . '/' . $file['filename'];
					if ( file_exists( $path ) ) {
						unlink( $path );
					}
					if ( file_exists( $path . '.disabled' ) ) {
						unlink( $path . '.disabled' );
					}
					Sandbox::unregister_file( (string) $file['filename'] );
				}
				break;
		}

		wp_safe_redirect( admin_url( 'admin.php?page=layrshift-sandbox&updated=1' ) );
		exit;
	}

	public static function handle_clear_log(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized', 'layrshift' ) );
		}

		check_admin_referer( 'layrshift_clear_log' );
		Logger::clear();
		wp_safe_redirect( admin_url( 'admin.php?page=layrshift-log&cleared=1' ) );
		exit;
	}

}
