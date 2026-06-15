<?php
/**
 * Ability log view.
 *
 * @package LayrShift
 */

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- View template scope.

$shell_mode = 'dev';
$dev_title  = __( 'Activity Log', 'layrshift' );
$active_tab = 'settings';

include LAYRSHIFT_PATH . 'admin/views/partials/app-shell-open.php';
?>
<section class="layrshift-dev-panel">
	<p class="layrshift-panel__lead">
		<?php
		printf(
			/* translators: %d: number of log entries */
			esc_html( _n( '%d invocation logged.', '%d invocations logged.', count( $entries ), 'layrshift' ) ),
			count( $entries )
		);
		?>
	</p>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="layrshift-actions">
		<?php wp_nonce_field( 'layrshift_clear_log' ); ?>
		<input type="hidden" name="action" value="layrshift_clear_log" />
		<?php submit_button( __( 'Clear Log', 'layrshift' ), 'secondary', 'submit', false ); ?>
	</form>

	<div class="layrshift-table-wrap">
		<table class="widefat striped layrshift-log-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Time', 'layrshift' ); ?></th>
					<th><?php esc_html_e( 'Ability', 'layrshift' ); ?></th>
					<th><?php esc_html_e( 'User', 'layrshift' ); ?></th>
					<th><?php esc_html_e( 'IP', 'layrshift' ); ?></th>
					<th><?php esc_html_e( 'Duration', 'layrshift' ); ?></th>
					<th><?php esc_html_e( 'Status', 'layrshift' ); ?></th>
					<th><?php esc_html_e( 'Details', 'layrshift' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( empty( $entries ) ) : ?>
					<tr><td colspan="7"><?php esc_html_e( 'No invocations logged yet.', 'layrshift' ); ?></td></tr>
				<?php else : ?>
					<?php foreach ( $entries as $entry ) : ?>
						<?php
						$entry_status = (string) ( $entry['status'] ?? '' );
						$pill_class   = 'error' === $entry_status ? 'disabled' : 'active';
						?>
						<tr>
							<td><?php echo esc_html( (string) ( $entry['timestamp'] ?? '' ) ); ?></td>
							<td><code class="layrshift-inline-code"><?php echo esc_html( (string) ( $entry['ability'] ?? '' ) ); ?></code></td>
							<td><?php echo esc_html( (string) ( $entry['user_login'] ?? '' ) ); ?></td>
							<td><?php echo esc_html( (string) ( $entry['ip'] ?? '' ) ); ?></td>
							<td><?php echo esc_html( (string) ( $entry['execution_time_ms'] ?? '' ) ); ?> ms</td>
							<td><span class="layrshift-status-pill layrshift-status-pill--<?php echo esc_attr( $pill_class ); ?>"><?php echo esc_html( $entry_status ); ?></span></td>
							<td>
								<details>
									<summary><?php esc_html_e( 'View', 'layrshift' ); ?></summary>
									<pre class="layrshift-log-detail"><?php echo esc_html( 'Input: ' . ( $entry['input'] ?? '' ) . "\nOutput: " . ( $entry['output'] ?? '' ) ); ?></pre>
								</details>
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
