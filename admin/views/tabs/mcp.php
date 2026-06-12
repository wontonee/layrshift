<?php
/**
 * MCP connect tab panel.
 *
 * @package LayrShift
 *
 * @var array<string, mixed> $mcp_connect
 * @var array<string, mixed> $settings
 * @var array<int, string>   $errors
 */

defined( 'ABSPATH' ) || exit;

$username          = (string) ( $mcp_connect['username'] ?? '' );
$display_password  = (string) ( $mcp_connect['display_password'] ?? 'YOUR-APP-PASSWORD' );
$new_password      = $mcp_connect['new_password'] ?? null;
$existing_password = $mcp_connect['existing_password'] ?? null;
$create_error      = $mcp_connect['create_error'] ?? null;
$existing_error    = $mcp_connect['existing_error'] ?? null;
$revoked           = ! empty( $mcp_connect['revoked'] );
$password_status   = is_array( $mcp_connect['password_status'] ?? null ) ? $mcp_connect['password_status'] : array( 'available' => false, 'message' => '' );
$mcp_passwords     = is_array( $mcp_connect['mcp_passwords'] ?? null ) ? $mcp_connect['mcp_passwords'] : array();
$mcp_config        = (string) ( $mcp_connect['mcp_config'] ?? '' );
$has_password      = 'YOUR-APP-PASSWORD' !== $display_password;
$has_existing      = ! empty( $mcp_passwords );
$existing_open     = null !== $existing_password || $existing_error instanceof \WP_Error;
$dt_format         = trim( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ) ?: 'Y-m-d H:i';

$requirements_ok = empty( $errors );
$abilities_ok    = ! empty( $settings['enabled'] ) && ! empty( $settings['risk_acknowledged'] );
?>
<section class="layrshift-mcp">
	<p class="layrshift-panel__lead"><?php esc_html_e( 'Connect Cursor, Claude Code, or any MCP client to this WordPress dev environment.', 'layrshift' ); ?></p>

	<ul class="layrshift-checklist">
		<li class="<?php echo $requirements_ok ? 'is-ok' : 'is-fail'; ?>">
			<?php echo $requirements_ok ? esc_html__( 'All requirements met', 'layrshift' ) : esc_html( implode( '; ', $errors ) ); ?>
		</li>
		<li class="<?php echo $abilities_ok ? 'is-ok' : 'is-fail'; ?>">
			<?php esc_html_e( 'AI Abilities enabled in Settings', 'layrshift' ); ?>
			<?php if ( ! $abilities_ok ) : ?>
				— <a href="<?php echo esc_url( \LayrShift\Admin\Admin::app_url( 'settings' ) ); ?>"><?php esc_html_e( 'Open Settings', 'layrshift' ); ?></a>
			<?php endif; ?>
		</li>
		<li class="is-ok">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=layrshift-abilities' ) ); ?>"><?php esc_html_e( 'Abilities Hub', 'layrshift' ); ?></a>
			<?php esc_html_e( '— enable or disable individual MCP tools', 'layrshift' ); ?>
		</li>
	</ul>

	<div class="layrshift-mcp-creds">
		<h3 class="layrshift-section-label"><?php esc_html_e( 'MCP credentials', 'layrshift' ); ?></h3>
		<p class="layrshift-mcp-creds__lead"><?php esc_html_e( 'Generate an application password here — no need to visit your WordPress profile.', 'layrshift' ); ?></p>

		<div class="layrshift-mcp-creds__user">
			<span class="layrshift-mcp-creds__user-label"><?php esc_html_e( 'Username', 'layrshift' ); ?></span>
			<code class="layrshift-inline-code" id="layrshift-mcp-username"><?php echo esc_html( $username ); ?></code>
			<button type="button" class="layrshift-btn layrshift-btn--secondary layrshift-btn--small layrshift-copy-btn" data-target="#layrshift-mcp-username" data-label="<?php esc_attr_e( 'Copy', 'layrshift' ); ?>"><?php esc_html_e( 'Copy', 'layrshift' ); ?></button>
		</div>

		<?php if ( ! $password_status['available'] ) : ?>
			<div class="layrshift-callout layrshift-callout--warning">
				<?php echo esc_html( (string) $password_status['message'] ); ?>
				<?php if ( 'unsupported' === ( $password_status['reason'] ?? '' ) ) : ?>
					<p class="layrshift-mcp-creds__hint"><?php esc_html_e( 'On local HTTP sites, add this to wp-config.php above the "That\'s all" line:', 'layrshift' ); ?></p>
					<code class="layrshift-inline-code">define( 'WP_ENVIRONMENT_TYPE', 'local' );</code>
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<?php if ( $revoked ) : ?>
			<div class="layrshift-callout layrshift-callout--info"><?php esc_html_e( 'Application password revoked.', 'layrshift' ); ?></div>
		<?php endif; ?>

		<?php if ( $create_error instanceof \WP_Error ) : ?>
			<div class="layrshift-callout layrshift-callout--warning"><?php echo esc_html( $create_error->get_error_message() ); ?></div>
		<?php endif; ?>

		<?php if ( is_string( $new_password ) ) : ?>
			<div class="layrshift-callout layrshift-callout--info">
				<p><?php esc_html_e( 'Application password generated and embedded in the config below. Copy it now — it will not be shown again.', 'layrshift' ); ?></p>
				<div class="layrshift-mcp-creds__pw-row">
					<code class="layrshift-mcp-creds__pw" id="layrshift-new-pw-value"><?php echo esc_html( $new_password ); ?></code>
					<button type="button" class="layrshift-btn layrshift-btn--secondary layrshift-btn--small layrshift-copy-btn" data-target="#layrshift-new-pw-value" data-label="<?php esc_attr_e( 'Copy password', 'layrshift' ); ?>"><?php esc_html_e( 'Copy password', 'layrshift' ); ?></button>
				</div>
			</div>
		<?php elseif ( is_string( $existing_password ) ) : ?>
			<div class="layrshift-callout layrshift-callout--info"><?php esc_html_e( 'Password accepted and embedded in the config below.', 'layrshift' ); ?></div>
		<?php endif; ?>

		<form method="post" action="<?php echo esc_url( \LayrShift\Admin\Admin::app_url( 'mcp' ) ); ?>" class="layrshift-mcp-creds__form">
			<?php wp_nonce_field( 'layrshift_create_password' ); ?>
			<div class="layrshift-field">
				<label for="layrshift-password-name"><?php esc_html_e( 'Password label (optional)', 'layrshift' ); ?></label>
				<input
					type="text"
					id="layrshift-password-name"
					name="layrshift_password_name"
					class="layrshift-input"
					placeholder="<?php esc_attr_e( 'e.g. Cursor on laptop', 'layrshift' ); ?>"
					maxlength="70"
				/>
			</div>
			<button type="submit" name="layrshift_create_password" class="layrshift-btn layrshift-btn--primary" <?php disabled( ! $password_status['available'] ); ?>>
				<?php
				echo esc_html(
					$has_existing
						? __( 'Generate another password', 'layrshift' )
						: __( 'Generate application password', 'layrshift' )
				);
				?>
			</button>
		</form>

		<div class="layrshift-mcp-creds__existing">
			<button type="button" class="layrshift-mcp-creds__toggle" id="layrshift-use-existing-toggle" aria-expanded="<?php echo $existing_open ? 'true' : 'false'; ?>" aria-controls="layrshift-use-existing-field">
				<?php esc_html_e( 'I already have an application password', 'layrshift' ); ?>
			</button>
			<div id="layrshift-use-existing-field" class="layrshift-mcp-creds__existing-panel" <?php echo $existing_open ? '' : 'hidden'; ?>>
				<form method="post" action="<?php echo esc_url( \LayrShift\Admin\Admin::app_url( 'mcp' ) ); ?>">
					<?php wp_nonce_field( 'layrshift_use_existing_password' ); ?>
					<div class="layrshift-field">
						<label for="layrshift-existing-password"><?php esc_html_e( 'Paste password value', 'layrshift' ); ?></label>
						<input
							type="text"
							id="layrshift-existing-password"
							name="layrshift_existing_password"
							class="layrshift-input layrshift-input--mono"
							placeholder="xxxx xxxx xxxx xxxx xxxx xxxx"
							autocomplete="off"
						/>
					</div>
					<button type="submit" name="layrshift_use_existing_password" class="layrshift-btn layrshift-btn--secondary"><?php esc_html_e( 'Use this password', 'layrshift' ); ?></button>
					<?php if ( $existing_error instanceof \WP_Error ) : ?>
						<p class="layrshift-status is-error"><?php echo esc_html( $existing_error->get_error_message() ); ?></p>
					<?php endif; ?>
					<p class="layrshift-mcp-creds__hint"><?php esc_html_e( 'Used only to fill the config preview — never stored on this site.', 'layrshift' ); ?></p>
				</form>
			</div>
		</div>

		<?php if ( ! empty( $mcp_passwords ) ) : ?>
			<details class="layrshift-mcp-creds__manage" <?php echo count( $mcp_passwords ) <= 3 ? 'open' : ''; ?>>
				<summary>
					<?php
					printf(
						/* translators: %d: number of LayrShift application passwords */
						esc_html( _n( 'Manage LayrShift password (%d)', 'Manage LayrShift passwords (%d)', count( $mcp_passwords ), 'layrshift' ) ),
						count( $mcp_passwords )
					);
					?>
				</summary>
				<div class="layrshift-table-wrap">
					<table class="widefat striped">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Name', 'layrshift' ); ?></th>
								<th><?php esc_html_e( 'Created', 'layrshift' ); ?></th>
								<th><?php esc_html_e( 'Last used', 'layrshift' ); ?></th>
								<th><?php esc_html_e( 'Actions', 'layrshift' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $mcp_passwords as $pw ) : ?>
								<?php
								$uuid         = (string) ( $pw['uuid'] ?? '' );
								$name         = (string) ( $pw['name'] ?? '' );
								$created      = ! empty( $pw['created'] ) ? wp_date( $dt_format, (int) $pw['created'] ) : __( 'Unknown', 'layrshift' );
								$last_used    = ! empty( $pw['last_used'] ) ? wp_date( $dt_format, (int) $pw['last_used'] ) : __( 'Never', 'layrshift' );
								$revoke_nonce = wp_create_nonce( 'layrshift_revoke_password_' . $uuid );
								?>
								<tr>
									<td><strong><?php echo esc_html( $name ); ?></strong></td>
									<td><?php echo esc_html( $created ); ?></td>
									<td><?php echo esc_html( $last_used ); ?></td>
									<td>
										<form method="post" action="<?php echo esc_url( \LayrShift\Admin\Admin::app_url( 'mcp' ) ); ?>" onsubmit="return confirm('<?php echo esc_js( __( 'Revoke this password? Clients using it will lose access.', 'layrshift' ) ); ?>');">
											<input type="hidden" name="layrshift_revoke_uuid" value="<?php echo esc_attr( $uuid ); ?>" />
											<input type="hidden" name="_wpnonce" value="<?php echo esc_attr( $revoke_nonce ); ?>" />
											<button type="submit" name="layrshift_revoke_password" class="layrshift-btn layrshift-btn--secondary layrshift-btn--small"><?php esc_html_e( 'Revoke', 'layrshift' ); ?></button>
										</form>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			</details>
		<?php endif; ?>
	</div>

	<?php if ( ! $has_password ) : ?>
		<div class="layrshift-callout layrshift-callout--warning">
			<?php esc_html_e( 'Generate or paste a password above — snippets below will show YOUR-APP-PASSWORD until you do.', 'layrshift' ); ?>
		</div>
	<?php endif; ?>

	<?php include LAYRSHIFT_PATH . 'admin/views/partials/mcp-client-config.php'; ?>

	<div class="layrshift-studio-verify">
		<h3 class="layrshift-section-label"><?php esc_html_e( 'Verify connection', 'layrshift' ); ?></h3>
		<p class="layrshift-mcp-creds__hint"><?php esc_html_e( 'Restart your MCP client, then ask it to run:', 'layrshift' ); ?></p>
		<code class="layrshift-inline-code layrshift-inline-code--block">layrshift/execute-php → return get_bloginfo('name');</code>
	</div>
</section>
