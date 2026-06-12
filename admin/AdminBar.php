<?php
/**
 * LayrShift admin bar MCP status chip.
 *
 * @package LayrShift
 */

declare( strict_types=1 );

namespace LayrShift\Admin;

use LayrShift\AbilityPolicy;
use LayrShift\Plugin;

/**
 * Admin bar ON / ERROR / OFF indicator and quick toggle.
 */
final class AdminBar {

	public static function init(): void {
		add_action( 'admin_bar_menu', array( self::class, 'register_menu' ), 998 );
		add_action( 'admin_head', array( self::class, 'render_assets' ) );
		add_action( 'wp_head', array( self::class, 'render_assets' ) );
		add_action( 'admin_post_layrshift_toggle_ai_abilities', array( self::class, 'handle_toggle' ) );
	}

	public static function register_menu( \WP_Admin_Bar $wp_admin_bar ): void {
		if ( ! self::current_user_can_manage() ) {
			return;
		}

		$status           = Plugin::get_abilities_status();
		$configured       = (bool) $status['configured'];
		$active           = (bool) $status['active'];
		$can_enable       = (bool) $status['can_enable'];
		$config_url       = admin_url( 'admin.php?page=' . Admin::APP_PAGE . '&tab=mcp' );
		$abilities_hub_url = admin_url( 'admin.php?page=' . AbilityPolicy::HUB_PAGE );
		$target           = $configured ? 'off' : 'on';
		$toggle_url       = wp_nonce_url(
			admin_url( 'admin-post.php?action=layrshift_toggle_ai_abilities&layrshift_target=' . $target ),
			'layrshift_toggle_ai_abilities'
		);

		$chip_title = __( 'LayrShift', 'layrshift' );
		$chip_class = 'layrshift-mcp-off';
		if ( $active ) {
			$chip_title = __( 'LayrShift ON', 'layrshift' );
			$chip_class = 'layrshift-mcp-on';
		} elseif ( $configured ) {
			$chip_title = __( 'LayrShift ERROR', 'layrshift' );
			$chip_class = 'layrshift-mcp-error';
		}

		$wp_admin_bar->add_node(
			array(
				'id'    => 'layrshift-mcp-status',
				'title' => esc_html( $chip_title ),
				'href'  => $config_url,
				'meta'  => array(
					'class' => $chip_class,
				),
			)
		);

		$status_label = __( 'AI Abilities: Off', 'layrshift' );
		if ( $active ) {
			$status_label = __( 'AI Abilities: On', 'layrshift' );
		} elseif ( $configured ) {
			$status_label = __( 'AI Abilities: Error', 'layrshift' );
		}

		$wp_admin_bar->add_node(
			array(
				'id'     => 'layrshift-mcp-status-label',
				'parent' => 'layrshift-mcp-status',
				'title'  => esc_html( $status_label ),
			)
		);

		if ( ! $can_enable ) {
			$wp_admin_bar->add_node(
				array(
					'id'     => 'layrshift-mcp-unavailable',
					'parent' => 'layrshift-mcp-status',
					'title'  => esc_html__( 'AI Abilities unavailable', 'layrshift' ),
					'href'   => $config_url,
				)
			);
		}

		if ( $can_enable ) {
			$wp_admin_bar->add_node(
				array(
					'id'     => 'layrshift-mcp-toggle',
					'parent' => 'layrshift-mcp-status',
					'title'  => $configured
						? esc_html__( 'Turn Off AI Abilities', 'layrshift' )
						: esc_html__( 'Turn On AI Abilities', 'layrshift' ),
					'href'   => $toggle_url,
					'meta'   => array(
						'class' => $configured ? 'layrshift-mcp-toggle-off' : 'layrshift-mcp-toggle-on',
					),
				)
			);
		}

		$wp_admin_bar->add_node(
			array(
				'id'     => 'layrshift-mcp-config',
				'parent' => 'layrshift-mcp-status',
				'title'  => esc_html__( 'Configuration', 'layrshift' ),
				'href'   => $config_url,
			)
		);

		$wp_admin_bar->add_node(
			array(
				'id'     => 'layrshift-mcp-abilities-hub',
				'parent' => 'layrshift-mcp-status',
				'title'  => esc_html__( 'Abilities Hub', 'layrshift' ),
				'href'   => $abilities_hub_url,
			)
		);
	}

	public static function render_assets(): void {
		if ( ! self::current_user_can_manage() || ! is_admin_bar_showing() ) {
			return;
		}

		$looks_production = self::looks_like_production();
		$confirm_message  = $looks_production
			? __(
				'This looks like a production site. LayrShift AI Abilities are intended for staging or development sites. Continue anyway?',
				'layrshift'
			)
			: __(
				'AI agents will be able to execute PHP code and access the filesystem. Continue?',
				'layrshift'
			);
		?>
		<style>
		#wp-admin-bar-layrshift-mcp-status.layrshift-mcp-on > .ab-item {
			background: #2563eb !important;
			color: #fff !important;
		}
		#wp-admin-bar-layrshift-mcp-status.layrshift-mcp-error > .ab-item {
			background: #996800 !important;
			color: #fff !important;
		}
		#wp-admin-bar-layrshift-mcp-status-label > .ab-item {
			cursor: default;
			font-weight: 600;
		}
		</style>
		<script>
		document.addEventListener('DOMContentLoaded', function () {
			var toggle = document.querySelector('#wp-admin-bar-layrshift-mcp-toggle.layrshift-mcp-toggle-on > .ab-item');
			if (!toggle) {
				return;
			}
			toggle.addEventListener('click', function (event) {
				if (!window.confirm(<?php echo wp_json_encode( $confirm_message ); ?>)) {
					event.preventDefault();
				}
			});
		});
		</script>
		<?php
	}

	public static function handle_toggle(): void {
		if ( ! self::current_user_can_manage() ) {
			wp_die( esc_html__( 'You are not allowed to manage LayrShift settings.', 'layrshift' ) );
		}

		check_admin_referer( 'layrshift_toggle_ai_abilities' );

		$target = isset( $_GET['layrshift_target'] ) ? sanitize_key( wp_unslash( (string) $_GET['layrshift_target'] ) ) : '';
		$result = false;

		if ( 'on' === $target ) {
			$result = Plugin::enable_abilities();
		} elseif ( 'off' === $target ) {
			$result = Plugin::disable_abilities();
		}

		$redirect = wp_get_referer();
		if ( ! is_string( $redirect ) || '' === $redirect ) {
			$redirect = admin_url( 'admin.php?page=' . Admin::APP_PAGE . '&tab=mcp' );
		}

		$redirect = add_query_arg(
			array(
				'layrshift_toggle_result' => $result ? $target : 'failed',
			),
			$redirect
		);

		wp_safe_redirect( $redirect );
		exit;
	}

	private static function current_user_can_manage(): bool {
		return current_user_can( 'manage_options' );
	}

	private static function looks_like_production(): bool {
		if ( function_exists( 'wp_get_environment_type' ) ) {
			return 'production' === wp_get_environment_type();
		}

		return false;
	}
}
