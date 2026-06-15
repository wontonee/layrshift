<?php
/**
 * Settings tab panel.
 *
 * @package LayrShift
 */

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- View template scope.

use LayrShift\Sandbox;

$sandbox_dir   = Sandbox::get_directory();
$abilities_url = admin_url( 'admin.php?page=layrshift-abilities' );
$sandbox_url   = admin_url( 'admin.php?page=layrshift-sandbox' );
$log_url       = admin_url( 'admin.php?page=layrshift-log' );
?>
<section class="layrshift-settings-panel">
	<p class="layrshift-panel__lead">
		<?php esc_html_e( 'Control how AI agents connect to this site — who can use abilities and what safety limits apply.', 'layrshift' ); ?>
	</p>

	<div class="layrshift-callout layrshift-callout--warning">
		<strong><?php esc_html_e( 'Dev/staging only.', 'layrshift' ); ?></strong>
		<?php esc_html_e( 'LayrShift grants AI agents PHP and filesystem access. Never enable on production.', 'layrshift' ); ?>
	</div>

	<form method="post" action="options.php" class="layrshift-settings-form">
		<?php settings_fields( 'layrshift_settings_group' ); ?>

		<div class="layrshift-settings-stack">
			<article class="layrshift-settings-card">
				<header class="layrshift-settings-card__head">
					<h2 class="layrshift-settings-card__title"><?php esc_html_e( 'MCP access', 'layrshift' ); ?></h2>
					<p class="layrshift-settings-card__desc">
						<?php esc_html_e( 'Turn abilities on for external MCP clients (Cursor, Claude Code, etc.) and choose who may invoke them.', 'layrshift' ); ?>
					</p>
				</header>
				<div class="layrshift-settings-card__body">
					<label class="layrshift-settings-toggle">
						<input type="checkbox" name="layrshift_settings[enabled]" value="1" <?php checked( ! empty( $settings['enabled'] ) ); ?> />
						<span class="layrshift-settings-toggle__text">
							<strong><?php esc_html_e( 'Enable AI Abilities', 'layrshift' ); ?></strong>
							<span><?php esc_html_e( 'Allow MCP clients to call LayrShift tools on this site.', 'layrshift' ); ?></span>
						</span>
					</label>

					<label class="layrshift-settings-toggle">
						<input type="checkbox" name="layrshift_settings[risk_acknowledged]" value="1" <?php checked( ! empty( $settings['risk_acknowledged'] ) ); ?> required />
						<span class="layrshift-settings-toggle__text">
							<strong><?php esc_html_e( 'Risk acknowledgment', 'layrshift' ); ?></strong>
							<span><?php esc_html_e( 'I understand this is for dev/staging only and accept full responsibility.', 'layrshift' ); ?></span>
						</span>
					</label>

					<div class="layrshift-settings-field">
						<label class="layrshift-settings-field__label" for="layrshift-allowed-admins">
							<?php esc_html_e( 'Allowed admin users', 'layrshift' ); ?>
						</label>
						<select id="layrshift-allowed-admins" class="layrshift-select" name="layrshift_settings[allowed_user_ids][]" multiple size="4">
							<?php foreach ( $admins as $admin ) : ?>
								<option value="<?php echo esc_attr( (string) $admin->ID ); ?>" <?php selected( in_array( (int) $admin->ID, (array) $settings['allowed_user_ids'], true ) ); ?>>
									<?php echo esc_html( $admin->display_name . ' (' . $admin->user_login . ')' ); ?>
								</option>
							<?php endforeach; ?>
						</select>
						<p class="layrshift-settings-field__hint"><?php esc_html_e( 'Hold Ctrl/Cmd to select multiple. Leave empty to allow all administrators.', 'layrshift' ); ?></p>
					</div>

					<label class="layrshift-settings-toggle">
						<input type="checkbox" name="layrshift_settings[enable_application_passwords]" value="1" <?php checked( ! empty( $settings['enable_application_passwords'] ) ); ?> />
						<span class="layrshift-settings-toggle__text">
							<strong><?php esc_html_e( 'Allow Application Passwords', 'layrshift' ); ?></strong>
							<span><?php esc_html_e( 'Re-enable Application Passwords when a security plugin (e.g. Wordfence) disabled them. Administrators only; requires AI Abilities enabled.', 'layrshift' ); ?></span>
						</span>
					</label>

					<p class="layrshift-settings-inline-link">
						<a href="<?php echo esc_url( $abilities_url ); ?>"><?php esc_html_e( 'Abilities Hub', 'layrshift' ); ?></a>
						<?php esc_html_e( '— enable or disable individual MCP tools.', 'layrshift' ); ?>
					</p>
				</div>
			</article>

			<article class="layrshift-settings-card">
				<header class="layrshift-settings-card__head">
					<h2 class="layrshift-settings-card__title"><?php esc_html_e( 'Safety limits', 'layrshift' ); ?></h2>
					<p class="layrshift-settings-card__desc">
						<?php esc_html_e( 'Guardrails for ability execution and where agent-written files are stored.', 'layrshift' ); ?>
					</p>
				</header>
				<div class="layrshift-settings-card__body">
					<div class="layrshift-settings-field layrshift-settings-field--inline">
						<label class="layrshift-settings-field__label" for="layrshift-exec-limit">
							<?php esc_html_e( 'PHP time limit', 'layrshift' ); ?>
						</label>
						<div class="layrshift-settings-field__control">
							<input type="number" id="layrshift-exec-limit" class="layrshift-input layrshift-input--narrow" min="5" max="120" name="layrshift_settings[exec_time_limit]" value="<?php echo esc_attr( (string) $settings['exec_time_limit'] ); ?>" />
							<span class="layrshift-settings-field__suffix"><?php esc_html_e( 'seconds', 'layrshift' ); ?></span>
						</div>
					</div>

					<label class="layrshift-settings-toggle">
						<input type="checkbox" name="layrshift_settings[https_enforcement]" value="1" <?php checked( ! empty( $settings['https_enforcement'] ) ); ?> />
						<span class="layrshift-settings-toggle__text">
							<strong><?php esc_html_e( 'Require HTTPS', 'layrshift' ); ?></strong>
							<span><?php esc_html_e( 'Reject MCP requests that are not served over HTTPS.', 'layrshift' ); ?></span>
						</span>
					</label>

					<label class="layrshift-settings-toggle">
						<input type="checkbox" name="layrshift_settings[restrict_core_deletion]" value="1" <?php checked( ! empty( $settings['restrict_core_deletion'] ) ); ?> />
						<span class="layrshift-settings-toggle__text">
							<strong><?php esc_html_e( 'Protect WordPress core', 'layrshift' ); ?></strong>
							<span><?php esc_html_e( 'Block deletion of files under wp-includes and wp-admin.', 'layrshift' ); ?></span>
						</span>
					</label>

					<div class="layrshift-settings-path">
						<span class="layrshift-settings-path__label"><?php esc_html_e( 'Agent file storage', 'layrshift' ); ?></span>
						<code class="layrshift-settings-path__value"><?php echo esc_html( $sandbox_dir ); ?></code>
						<p class="layrshift-settings-path__hint">
							<?php esc_html_e( 'PHP files that agents deploy for testing are saved here. Manage them from the Agent Sandbox tool below.', 'layrshift' ); ?>
						</p>
					</div>
				</div>
			</article>
		</div>

		<div class="layrshift-settings-save">
			<?php submit_button( __( 'Save settings', 'layrshift' ), 'primary layrshift-btn', 'submit', false ); ?>
		</div>
	</form>

	<section class="layrshift-tools-section" aria-labelledby="layrshift-tools-heading">
		<header class="layrshift-tools-section__head">
			<h2 id="layrshift-tools-heading" class="layrshift-tools-section__title"><?php esc_html_e( 'Monitoring & review', 'layrshift' ); ?></h2>
			<p class="layrshift-tools-section__desc">
				<?php esc_html_e( 'After agents start working, use these screens to inspect what they changed and what abilities they called.', 'layrshift' ); ?>
			</p>
		</header>

		<div class="layrshift-tool-cards">
			<a class="layrshift-tool-card" href="<?php echo esc_url( $sandbox_url ); ?>">
				<span class="layrshift-tool-card__icon dashicons dashicons-media-code" aria-hidden="true"></span>
				<span class="layrshift-tool-card__body">
					<span class="layrshift-tool-card__title"><?php esc_html_e( 'Agent Sandbox', 'layrshift' ); ?></span>
					<span class="layrshift-tool-card__desc">
						<?php esc_html_e( 'Review PHP files agents uploaded for testing. Enable, disable, or delete them — and use safe mode if something breaks the site.', 'layrshift' ); ?>
					</span>
				</span>
				<span class="layrshift-tool-card__arrow" aria-hidden="true">→</span>
			</a>

			<a class="layrshift-tool-card" href="<?php echo esc_url( $log_url ); ?>">
				<span class="layrshift-tool-card__icon dashicons dashicons-list-view" aria-hidden="true"></span>
				<span class="layrshift-tool-card__body">
					<span class="layrshift-tool-card__title"><?php esc_html_e( 'Ability Activity Log', 'layrshift' ); ?></span>
					<span class="layrshift-tool-card__desc">
						<?php esc_html_e( 'Audit trail of every MCP ability call — which tool ran, who triggered it, when, and whether it succeeded.', 'layrshift' ); ?>
					</span>
				</span>
				<span class="layrshift-tool-card__arrow" aria-hidden="true">→</span>
			</a>
		</div>
	</section>
</section>

