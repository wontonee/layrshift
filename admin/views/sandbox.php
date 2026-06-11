<?php
/**
 * Sandbox manager view.
 *
 * @package LayrShift
 */

defined( 'ABSPATH' ) || exit;

$safe_mode  = \LayrShift\Sandbox::is_safe_mode_active();
$shell_mode = 'dev';
$dev_title  = __( 'Agent Sandbox', 'layrshift' );
$active_tab = 'settings';

include LAYRSHIFT_PATH . 'admin/views/partials/app-shell-open.php';
?>
<section class="layrshift-dev-panel">
	<?php if ( $safe_mode ) : ?>
		<div class="layrshift-callout layrshift-callout--warning">
			<strong><?php esc_html_e( 'Safe mode is active.', 'layrshift' ); ?></strong>
			<?php esc_html_e( 'Sandbox files are not being loaded.', 'layrshift' ); ?>
		</div>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="layrshift-actions">
			<?php wp_nonce_field( 'layrshift_sandbox_action' ); ?>
			<input type="hidden" name="action" value="layrshift_sandbox_action" />
			<input type="hidden" name="layrshift_action" value="exit_safe_mode" />
			<?php submit_button( __( 'Exit Safe Mode', 'layrshift' ), 'primary', 'submit', false ); ?>
		</form>
	<?php else : ?>
		<div class="layrshift-callout layrshift-callout--info">
			<?php
			printf(
				/* translators: %s: safe mode URL parameter hint */
				esc_html__( 'If a sandbox file breaks your site, visit any admin URL with %s to enable safe mode.', 'layrshift' ),
				'<code class="layrshift-inline-code">?layrshift-safe-mode=1</code>'
			);
			?>
		</div>
	<?php endif; ?>

	<div class="layrshift-actions">
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline;">
			<?php wp_nonce_field( 'layrshift_sandbox_action' ); ?>
			<input type="hidden" name="action" value="layrshift_sandbox_action" />
			<input type="hidden" name="layrshift_action" value="disable_all" />
			<?php submit_button( __( 'Disable All', 'layrshift' ), 'secondary', 'submit', false ); ?>
		</form>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline;">
			<?php wp_nonce_field( 'layrshift_sandbox_action' ); ?>
			<input type="hidden" name="action" value="layrshift_sandbox_action" />
			<input type="hidden" name="layrshift_action" value="enable_all" />
			<?php submit_button( __( 'Enable All', 'layrshift' ), 'secondary', 'submit', false ); ?>
		</form>
	</div>

	<div class="layrshift-table-wrap">
		<table class="widefat striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Filename', 'layrshift' ); ?></th>
					<th><?php esc_html_e( 'Status', 'layrshift' ); ?></th>
					<th><?php esc_html_e( 'Size', 'layrshift' ); ?></th>
					<th><?php esc_html_e( 'Last Modified', 'layrshift' ); ?></th>
					<th><?php esc_html_e( 'Actions', 'layrshift' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( empty( $files ) ) : ?>
					<tr><td colspan="5"><?php esc_html_e( 'No sandbox files yet.', 'layrshift' ); ?></td></tr>
				<?php else : ?>
					<?php foreach ( $files as $file ) : ?>
						<?php
						$status       = (string) ( $file['status'] ?? '' );
						$status_class = 'disabled' === $status ? 'disabled' : 'active';
						?>
						<tr>
							<td><code class="layrshift-inline-code"><?php echo esc_html( (string) $file['filename'] ); ?></code></td>
							<td><span class="layrshift-status-pill layrshift-status-pill--<?php echo esc_attr( $status_class ); ?>"><?php echo esc_html( $status ); ?></span></td>
							<td><?php echo esc_html( size_format( (int) $file['size'] ) ); ?></td>
							<td><?php echo esc_html( (string) $file['last_modified'] ); ?></td>
							<td>
								<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline">
									<?php wp_nonce_field( 'layrshift_sandbox_action' ); ?>
									<input type="hidden" name="action" value="layrshift_sandbox_action" />
									<input type="hidden" name="filename" value="<?php echo esc_attr( (string) $file['filename'] ); ?>" />
									<?php if ( 'disabled' === $status ) : ?>
										<input type="hidden" name="layrshift_action" value="enable" />
										<button type="submit" class="button button-small"><?php esc_html_e( 'Enable', 'layrshift' ); ?></button>
									<?php else : ?>
										<input type="hidden" name="layrshift_action" value="disable" />
										<button type="submit" class="button button-small"><?php esc_html_e( 'Disable', 'layrshift' ); ?></button>
									<?php endif; ?>
								</form>
								<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline" onsubmit="return confirm('<?php echo esc_js( __( 'Delete this sandbox file?', 'layrshift' ) ); ?>');">
									<?php wp_nonce_field( 'layrshift_sandbox_action' ); ?>
									<input type="hidden" name="action" value="layrshift_sandbox_action" />
									<input type="hidden" name="layrshift_action" value="delete" />
									<input type="hidden" name="filename" value="<?php echo esc_attr( (string) $file['filename'] ); ?>" />
									<button type="submit" class="button button-small button-link-delete"><?php esc_html_e( 'Delete', 'layrshift' ); ?></button>
								</form>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
		</table>
	</div>
</section>
<?php
include LAYRSHIFT_PATH . 'admin/views/partials/app-shell-close.php';
