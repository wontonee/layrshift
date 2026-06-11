<?php
/**
 * Abilities Hub admin screen.
 *
 * @package LayrShift
 */

declare( strict_types=1 );

namespace LayrShift\Admin;

use LayrShift\AbilityPolicy;
use LayrShift\Auth;
use LayrShift\Plugin;

/**
 * Lists and manages LayrShift (and other) MCP abilities.
 */
final class AbilitiesHub {

	public static function init(): void {
		add_action( 'admin_menu', array( self::class, 'register_menu' ), 20 );
		add_action( 'admin_init', array( self::class, 'handle_actions' ) );
		add_action( 'wp_ajax_layrshift_ability_toggle', array( self::class, 'handle_ajax_toggle' ) );
		add_action( 'admin_enqueue_scripts', array( self::class, 'enqueue_assets' ) );
		add_filter( 'admin_body_class', array( self::class, 'admin_body_class' ) );
	}

	public static function admin_body_class( string $classes ): string {
		if ( ! is_admin() ) {
			return $classes;
		}

		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( (string) $_GET['page'] ) ) : '';
		if ( AbilityPolicy::HUB_PAGE === $page ) {
			$classes .= ' layrshift-admin';
		}

		return $classes;
	}

	public static function register_menu(): void {
		add_submenu_page(
			Admin::APP_PAGE,
			__( 'Abilities Hub', 'layrshift' ),
			__( 'Abilities Hub', 'layrshift' ),
			'manage_options',
			AbilityPolicy::HUB_PAGE,
			array( self::class, 'render_page' )
		);
	}

	public static function enqueue_assets( string $hook ): void {
		if ( false === strpos( $hook, AbilityPolicy::HUB_PAGE ) ) {
			return;
		}

		wp_enqueue_style(
			'layrshift-admin',
			LAYRSHIFT_URL . 'admin/assets/admin.css',
			array(),
			LAYRSHIFT_VERSION
		);

		wp_enqueue_style(
			'layrshift-abilities-hub',
			LAYRSHIFT_URL . 'admin/assets/abilities-hub.css',
			array( 'layrshift-admin' ),
			LAYRSHIFT_VERSION
		);

		wp_enqueue_script(
			'layrshift-abilities-hub',
			LAYRSHIFT_URL . 'admin/assets/abilities-hub.js',
			array(),
			LAYRSHIFT_VERSION,
			true
		);
	}

	public static function handle_actions(): void {
		if ( ! isset( $_POST['layrshift_ability_hub_action'] ) ) {
			return;
		}

		if ( ! Auth::current_user_allowed() ) {
			return;
		}

		check_admin_referer( 'layrshift_ability_hub_action' );

		$action = sanitize_key( wp_unslash( (string) $_POST['layrshift_ability_hub_action'] ) );
		$rules  = AbilityPolicy::get_rules();

		if ( 'bulk_update' === $action ) {
			$bulk = sanitize_key(
				wp_unslash(
					(string) ( $_POST['bulk_action'] ?? $_POST['bulk_action2'] ?? '' )
				)
			);
			if ( '' === $bulk || '-1' === $bulk ) {
				wp_safe_redirect( admin_url( 'admin.php?page=' . AbilityPolicy::HUB_PAGE ) );
				exit;
			}

			$names = isset( $_POST['ability_names'] ) && is_array( $_POST['ability_names'] )
				? array_map( 'sanitize_text_field', wp_unslash( $_POST['ability_names'] ) )
				: array();

			foreach ( $names as $name ) {
				if ( ! AbilityPolicy::is_valid_ability_name( $name ) || AbilityPolicy::is_hub_protected( $name ) ) {
					continue;
				}
				$rules[ $name ] = array( 'disabled' => 'disable' === $bulk );
			}

			AbilityPolicy::update_rules( $rules );
			wp_safe_redirect( admin_url( 'admin.php?page=' . AbilityPolicy::HUB_PAGE . '&layrshift_result=bulk_updated' ) );
			exit;
		}

		$name = sanitize_text_field( wp_unslash( (string) ( $_POST['ability_name'] ?? '' ) ) );
		if ( ! AbilityPolicy::is_valid_ability_name( $name ) || AbilityPolicy::is_hub_protected( $name ) ) {
			wp_safe_redirect( admin_url( 'admin.php?page=' . AbilityPolicy::HUB_PAGE . '&layrshift_result=invalid' ) );
			exit;
		}

		if ( 'toggle_disabled' === $action ) {
			$disabled = ! ( $rules[ $name ]['disabled'] ?? false );
		} elseif ( 'enable' === $action || 'disable' === $action ) {
			$disabled = 'disable' === $action;
		} else {
			wp_safe_redirect( admin_url( 'admin.php?page=' . AbilityPolicy::HUB_PAGE . '&layrshift_result=invalid' ) );
			exit;
		}

		$rules[ $name ] = array( 'disabled' => $disabled );
		AbilityPolicy::update_rules( $rules );
		wp_safe_redirect( admin_url( 'admin.php?page=' . AbilityPolicy::HUB_PAGE . '&layrshift_result=updated' ) );
		exit;
	}

	public static function handle_ajax_toggle(): void {
		if ( ! Auth::current_user_allowed() ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'layrshift' ) ), 403 );
		}

		if ( ! check_ajax_referer( 'layrshift_ability_hub_action', false, false ) ) {
			wp_send_json_error( array( 'message' => __( 'Your session expired. Reload the page.', 'layrshift' ) ), 403 );
		}

		$name = sanitize_text_field( wp_unslash( (string) ( $_POST['ability_name'] ?? '' ) ) );
		if ( ! AbilityPolicy::is_valid_ability_name( $name ) || AbilityPolicy::is_hub_protected( $name ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid ability name.', 'layrshift' ) ), 400 );
		}

		$rules = AbilityPolicy::get_rules();
		$disabled = ! ( $rules[ $name ]['disabled'] ?? false );
		$rules[ $name ] = array( 'disabled' => $disabled );
		AbilityPolicy::update_rules( $rules );

		wp_send_json_success(
			array(
				'disabled' => $disabled,
				'status'   => $disabled ? __( 'Disabled', 'layrshift' ) : __( 'Enabled', 'layrshift' ),
				'button'   => $disabled ? __( 'Enable', 'layrshift' ) : __( 'Disable', 'layrshift' ),
			)
		);
	}

	public static function render_page(): void {
		if ( ! Auth::current_user_allowed() ) {
			return;
		}

		$groups           = self::collect_rows();
		$result           = isset( $_GET['layrshift_result'] ) ? sanitize_key( wp_unslash( (string) $_GET['layrshift_result'] ) ) : '';
		$expanded_source  = array_key_first( $groups );
		$divider_done     = false;
		$seen_layrshift   = false;
		?>
		<div
			class="wrap layrshift-hub layrshift-wrap"
			data-alloff-label="<?php esc_attr_e( 'All disabled', 'layrshift' ); ?>"
			data-confirm-disable="<?php esc_attr_e( 'Disable the %d selected abilities? You can re-enable them anytime.', 'layrshift' ); ?>"
		>
			<div class="wrap-title">
				<div>
					<h1><?php esc_html_e( 'Abilities Hub', 'layrshift' ); ?></h1>
					<p class="description">
						<?php
						printf(
							/* translators: %s: settings page link */
							esc_html__( 'Manage every ability exposed to AI agents. Disabled abilities are removed from MCP discovery while AI Abilities are enabled on the %s tab.', 'layrshift' ),
							'<a href="' . esc_url( admin_url( 'admin.php?page=layrshift&tab=settings' ) ) . '">' . esc_html__( 'Settings', 'layrshift' ) . '</a>'
						);
						?>
					</p>
				</div>
			</div>
			<?php self::render_notice( $result ); ?>
			<?php if ( empty( $groups ) ) : ?>
				<div class="layrshift-callout layrshift-callout--info">
					<p><?php esc_html_e( 'No abilities are currently registered.', 'layrshift' ); ?></p>
				</div>
			<?php else : ?>
				<form method="post" id="layrshift-abilities-bulk">
					<?php wp_nonce_field( 'layrshift_ability_hub_action' ); ?>
					<input type="hidden" name="layrshift_ability_hub_action" value="bulk_update" />
					<?php self::render_bulk_actions( 'top' ); ?>
					<?php foreach ( $groups as $source => $rows ) : ?>
						<?php
						$is_layrshift = 'layrshift' === $source;
						if ( ! $is_layrshift && $seen_layrshift && ! $divider_done ) {
							self::render_other_plugins_divider();
							$divider_done = true;
						}
						$seen_layrshift = $seen_layrshift || $is_layrshift;
						self::render_group_section( $source, $rows, $expanded_source );
						?>
					<?php endforeach; ?>
					<?php self::render_bulk_actions( 'bottom' ); ?>
				</form>
			<?php endif; ?>
		</div>
		<?php
	}

	private static function render_other_plugins_divider(): void {
		?>
		<div class="layrshift-hub-divider"><?php esc_html_e( 'Other plugins', 'layrshift' ); ?></div>
		<?php
	}

	/**
	 * @param list<array<string, mixed>> $abilities
	 */
	private static function render_group_section( string $source, array $abilities, ?string $expanded_source ): void {
		$open = $source === $expanded_source ? ' open' : '';
		?>
		<details class="layrshift-hub-section"<?php echo esc_attr( $open ); ?>>
			<summary class="layrshift-hub-header">
				<?php self::render_select_all( sprintf(
					/* translators: %s: provider name */
					__( 'Select all abilities from %s', 'layrshift' ),
					$source
				) ); ?>
				<h2>
					<?php echo esc_html( ucfirst( $source ) ); ?>
					<?php self::render_header_meta( $abilities ); ?>
				</h2>
			</summary>
			<?php self::render_group_body( $abilities ); ?>
		</details>
		<?php
	}

	/**
	 * @param list<array<string, mixed>> $abilities
	 */
	private static function render_group_body( array $abilities ): void {
		$by_category = self::group_by_category( $abilities );
		if ( count( $by_category ) > 1 ) {
			foreach ( $by_category as $category => $rows ) {
				self::render_category_subsection( $category, $rows );
			}
			return;
		}
		?>
		<div class="layrshift-hub-rows">
			<?php foreach ( $abilities as $ability ) : ?>
				<?php self::render_row( $ability ); ?>
			<?php endforeach; ?>
		</div>
		<?php
	}

	/**
	 * @param list<array<string, mixed>> $rows
	 */
	private static function render_category_subsection( string $category, array $rows ): void {
		$label = '' !== $category ? $category : __( 'Other', 'layrshift' );
		?>
		<details class="layrshift-hub-subsection" open>
			<summary class="layrshift-hub-subheader">
				<?php self::render_select_all( sprintf(
					/* translators: %s: category name */
					__( 'Select all abilities in %s', 'layrshift' ),
					$label
				) ); ?>
				<h3>
					<?php echo esc_html( $label ); ?>
					<?php self::render_header_meta( $rows ); ?>
				</h3>
			</summary>
			<div class="layrshift-hub-rows">
				<?php foreach ( $rows as $ability ) : ?>
					<?php self::render_row( $ability ); ?>
				<?php endforeach; ?>
			</div>
		</details>
		<?php
	}

	/**
	 * @param array<string, mixed> $ability
	 */
	private static function render_row( array $ability ): void {
		$row_class = 'layrshift-hub-row ' . ( $ability['disabled'] ? 'is-off' : 'is-on' );
		if ( ! empty( $ability['protected'] ) ) {
			$row_class .= ' is-protected';
		}
		?>
		<div class="<?php echo esc_attr( $row_class ); ?>">
			<label class="layrshift-hub-select">
				<span class="screen-reader-text">
					<?php
					echo esc_html(
						sprintf(
							/* translators: %s: ability name */
							__( 'Select %s', 'layrshift' ),
							$ability['name']
						)
					);
					?>
				</span>
				<input
					type="checkbox"
					name="ability_names[]"
					value="<?php echo esc_attr( (string) $ability['name'] ); ?>"
					form="layrshift-abilities-bulk"
					<?php disabled( ! empty( $ability['protected'] ) ); ?>
				/>
			</label>
			<?php self::render_row_main( $ability ); ?>
			<?php self::render_row_pills( $ability ); ?>
			<?php self::render_row_actions( $ability ); ?>
		</div>
		<?php
	}

	/**
	 * @param array<string, mixed> $ability
	 */
	private static function render_row_main( array $ability ): void {
		$slug = self::display_slug( (string) $ability['name'] );
		if ( '' === (string) $ability['description'] ) {
			?>
			<div class="layrshift-hub-main layrshift-hub-main--plain">
				<span class="slug" title="<?php echo esc_attr( (string) $ability['name'] ); ?>"><?php echo esc_html( $slug ); ?></span>
				<span class="desc"><?php echo esc_html( (string) $ability['label'] ); ?></span>
			</div>
			<?php
			return;
		}
		?>
		<details class="layrshift-hub-main">
			<summary class="layrshift-hub-summary">
				<span class="slug" title="<?php echo esc_attr( (string) $ability['name'] ); ?>"><?php echo esc_html( $slug ); ?></span>
				<span class="desc"><?php echo esc_html( (string) $ability['label'] ); ?></span>
			</summary>
			<div class="layrshift-hub-detail">
				<p class="desc-full"><?php echo esc_html( (string) $ability['description'] ); ?></p>
			</div>
		</details>
		<?php
	}

	/**
	 * @param array<string, mixed> $ability
	 */
	private static function render_row_pills( array $ability ): void {
		$mcp_type = (string) ( $ability['mcp_type'] ?? 'tool' );
		?>
		<div class="layrshift-hub-pills">
			<?php if ( in_array( $mcp_type, array( 'prompt', 'resource' ), true ) ) : ?>
				<span class="pill mcp"><?php echo esc_html( $mcp_type ); ?></span>
			<?php else : ?>
				<span class="pill mcp"><?php esc_html_e( 'tool', 'layrshift' ); ?></span>
			<?php endif; ?>
			<span class="pill status <?php echo ! empty( $ability['disabled'] ) ? 'is-disabled' : 'is-enabled'; ?>">
				<?php echo esc_html( (string) $ability['status'] ); ?>
			</span>
			<?php if ( ! empty( $ability['protected'] ) ) : ?>
				<span class="pill protected"><?php esc_html_e( 'Protected', 'layrshift' ); ?></span>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * @param array<string, mixed> $ability
	 */
	private static function render_row_actions( array $ability ): void {
		?>
		<div class="layrshift-hub-actions">
			<?php if ( empty( $ability['protected'] ) ) : ?>
				<form method="post">
					<?php wp_nonce_field( 'layrshift_ability_hub_action' ); ?>
					<input type="hidden" name="layrshift_ability_hub_action" value="toggle_disabled" />
					<input type="hidden" name="ability_name" value="<?php echo esc_attr( (string) $ability['name'] ); ?>" />
					<button type="submit" class="action-btn">
						<?php
						echo esc_html(
							! empty( $ability['disabled'] )
								? __( 'Enable', 'layrshift' )
								: __( 'Disable', 'layrshift' )
						);
						?>
					</button>
				</form>
			<?php endif; ?>
		</div>
		<?php
	}

	private static function render_select_all( string $label ): void {
		?>
		<label class="layrshift-hub-select-all">
			<span class="screen-reader-text"><?php echo esc_html( $label ); ?></span>
			<input type="checkbox" class="layrshift-hub-select-all-input" />
		</label>
		<?php
	}

	/**
	 * @param list<array<string, mixed>> $abilities
	 */
	private static function render_header_meta( array $abilities ): void {
		$total   = count( $abilities );
		$enabled = 0;
		foreach ( $abilities as $ability ) {
			if ( empty( $ability['disabled'] ) ) {
				++$enabled;
			}
		}
		?>
		<span class="count"><?php echo esc_html( $enabled === $total ? (string) $total : $enabled . ' / ' . $total ); ?></span>
		<?php if ( 0 === $enabled && $total > 0 ) : ?>
			<span class="pill status is-disabled layrshift-hub-alloff"><?php esc_html_e( 'All disabled', 'layrshift' ); ?></span>
		<?php endif; ?>
		<?php
	}

	private static function render_bulk_actions( string $position ): void {
		$suffix = 'bottom' === $position ? '2' : '';
		?>
		<div class="tablenav <?php echo esc_attr( $position ); ?>">
			<div class="alignleft actions bulkactions">
				<label for="layrshift-bulk-action-selector-<?php echo esc_attr( $position ); ?>" class="screen-reader-text">
					<?php esc_html_e( 'Select bulk action', 'layrshift' ); ?>
				</label>
				<select name="bulk_action<?php echo esc_attr( $suffix ); ?>" id="layrshift-bulk-action-selector-<?php echo esc_attr( $position ); ?>">
					<option value="-1"><?php esc_html_e( 'Bulk actions', 'layrshift' ); ?></option>
					<option value="enable"><?php esc_html_e( 'Enable', 'layrshift' ); ?></option>
					<option value="disable"><?php esc_html_e( 'Disable', 'layrshift' ); ?></option>
				</select>
				<button type="submit" class="button action"><?php esc_html_e( 'Apply', 'layrshift' ); ?></button>
			</div>
		</div>
		<?php
	}

	/**
	 * @param list<array<string, mixed>> $abilities
	 * @return array<string, list<array<string, mixed>>>
	 */
	private static function group_by_category( array $abilities ): array {
		$groups = array();
		foreach ( $abilities as $ability ) {
			$groups[ (string) $ability['category'] ][] = $ability;
		}

		uksort(
			$groups,
			static function ( string $a, string $b ): int {
				if ( '' === $a || '' === $b ) {
					return '' === $a ? 1 : -1;
				}
				return strcasecmp( $a, $b );
			}
		);

		return $groups;
	}

	private static function display_slug( string $ability_name ): string {
		$parts = explode( '/', $ability_name, 2 );
		return ( $parts[1] ?? '' ) !== '' ? $parts[1] : $ability_name;
	}

	private static function render_notice( string $result ): void {
		$messages = array(
			'updated'      => array( 'success', __( 'Ability rule updated.', 'layrshift' ) ),
			'bulk_updated' => array( 'success', __( 'Ability rules updated.', 'layrshift' ) ),
			'invalid'      => array( 'error', __( 'Invalid ability name.', 'layrshift' ) ),
		);
		if ( ! isset( $messages[ $result ] ) ) {
			return;
		}
		list( $type, $text ) = $messages[ $result ];
		printf( '<div class="notice notice-%s is-dismissible"><p>%s</p></div>', esc_attr( $type ), esc_html( $text ) );
	}

	/**
	 * @return array<string, list<array{name: string, label: string, description: string, category: string, mcp_type: string, status: string, disabled: bool, protected: bool}>>
	 */
	private static function collect_rows(): array {
		if ( ! function_exists( 'wp_get_abilities' ) ) {
			return array();
		}

		$rules  = AbilityPolicy::get_rules();
		$groups = array();
		$seen   = array();

		foreach ( wp_get_abilities() as $ability ) {
			$row = self::build_row( $ability, $rules );
			if ( null === $row ) {
				continue;
			}
			$seen[ $row['name'] ]           = true;
			$prefix                         = explode( '/', $row['name'], 2 )[0];
			$groups[ $prefix ][] = $row;
		}

		foreach ( $rules as $name => $rule ) {
			if ( isset( $seen[ $name ] ) || AbilityPolicy::is_hub_protected( $name ) || empty( $rule['disabled'] ) ) {
				continue;
			}
			$prefix           = explode( '/', $name, 2 )[0];
			$groups[ $prefix ][] = array(
				'name'        => $name,
				'label'       => __( 'Previously registered ability', 'layrshift' ),
				'description' => '',
				'category'    => '',
				'mcp_type'    => 'tool',
				'status'      => __( 'Disabled', 'layrshift' ),
				'disabled'    => true,
				'protected'   => false,
			);
		}

		uksort(
			$groups,
			static function ( string $a, string $b ): int {
				if ( 'layrshift' === $a ) {
					return -1;
				}
				if ( 'layrshift' === $b ) {
					return 1;
				}
				return strcasecmp( $a, $b );
			}
		);

		foreach ( $groups as $source => $rows ) {
			usort( $rows, static fn( array $a, array $b ): int => $a['name'] <=> $b['name'] );
			$groups[ $source ] = $rows;
		}

		return $groups;
	}

	/**
	 * @param array<string, array{disabled: bool}> $rules
	 * @return array{name: string, label: string, description: string, category: string, mcp_type: string, status: string, disabled: bool, protected: bool}|null
	 */
	private static function build_row( \WP_Ability $ability, array $rules ): ?array {
		$name = $ability->get_name();
		if ( AbilityPolicy::is_hub_protected( $name ) ) {
			return null;
		}

		$meta = $ability->get_meta();
		$mcp  = $meta['mcp'] ?? null;
		if ( ! is_array( $mcp ) || empty( $mcp['public'] ) ) {
			return null;
		}

		$protected = AbilityPolicy::is_hub_protected( $name );
		$disabled  = ! $protected && ( $rules[ $name ]['disabled'] ?? false );
		$cat_slug  = $ability->get_category();
		$category  = $cat_slug;
		if ( function_exists( 'wp_get_ability_category' ) ) {
			$cat_obj = wp_get_ability_category( $cat_slug );
			if ( $cat_obj ) {
				$category = $cat_obj->get_label();
			}
		}

		return array(
			'name'        => $name,
			'label'       => $ability->get_label(),
			'description' => $ability->get_description(),
			'category'    => $category,
			'mcp_type'    => (string) ( $mcp['type'] ?? 'tool' ),
			'status'      => $disabled ? __( 'Disabled', 'layrshift' ) : __( 'Enabled', 'layrshift' ),
			'disabled'    => $disabled,
			'protected'   => $protected,
		);
	}
}
