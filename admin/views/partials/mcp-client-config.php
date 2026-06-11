<?php
/**
 * Connection studio — MCP client setup (LayrShift pattern).
 *
 * @package LayrShift
 *
 * @var array<string, mixed> $mcp_connect
 */

defined( 'ABSPATH' ) || exit;

$mcp_url                 = (string) ( $mcp_connect['mcp_url'] ?? '' );
$username                = (string) ( $mcp_connect['username'] ?? '' );
$display_password        = (string) ( $mcp_connect['display_password'] ?? 'YOUR-APP-PASSWORD' );
$password_is_placeholder = ! empty( $mcp_connect['password_is_placeholder'] );
$server_name             = (string) ( $mcp_connect['mcp_server_name'] ?? 'layrshift' );
$name_placeholder        = (string) ( $mcp_connect['name_placeholder'] ?? '__LAYRSHIFT_MCP_NAME__' );
$pw_slot                 = (string) ( $mcp_connect['pw_slot'] ?? '__LAYRSHIFT_PW_SLOT__' );
$self_signed             = ! empty( $mcp_connect['self_signed_https'] );
$client_labels           = is_array( $mcp_connect['client_labels'] ?? null ) ? $mcp_connect['client_labels'] : array();
$client_groups           = is_array( $mcp_connect['client_groups'] ?? null ) ? $mcp_connect['client_groups'] : array();
$client_configs          = is_array( $mcp_connect['client_configs'] ?? null ) ? $mcp_connect['client_configs'] : array();
$paste_prompt            = (string) ( $mcp_connect['paste_prompt'] ?? '' );
$paste_template          = (string) ( $mcp_connect['paste_prompt_template'] ?? '' );
$default_client          = 'cursor';

$studio_payload = array(
	'configs'               => $client_configs,
	'client'                => $default_client,
	'defaultName'           => $server_name,
	'pasteTemplate'         => $paste_template,
	'namePlaceholder'       => $name_placeholder,
	'passwordSentinel'      => $pw_slot,
	'passwordValue'         => $display_password,
	'passwordIsPlaceholder' => $password_is_placeholder,
	'copiedLabel'           => __( 'Copied', 'layrshift' ),
	'shellBadge'            => __( 'Shell command', 'layrshift' ),
);
?>
<div class="layrshift-connection-studio" id="layrshift-connection-studio">
	<header class="layrshift-connection-studio__header">
		<div>
			<h3 class="layrshift-connection-studio__title"><?php esc_html_e( 'Connection studio', 'layrshift' ); ?></h3>
			<p class="layrshift-connection-studio__lead"><?php esc_html_e( 'Pick how you want to wire your MCP client — agent-assisted or copy a ready-made snippet.', 'layrshift' ); ?></p>
		</div>
		<div class="layrshift-segment" role="tablist" aria-label="<?php esc_attr_e( 'Connection method', 'layrshift' ); ?>">
			<button type="button" class="layrshift-segment__btn is-active" role="tab" aria-selected="true" data-studio-mode="agent" id="layrshift-studio-tab-agent">
				<?php esc_html_e( 'Agent setup', 'layrshift' ); ?>
			</button>
			<button type="button" class="layrshift-segment__btn" role="tab" aria-selected="false" data-studio-mode="snippet" id="layrshift-studio-tab-snippet">
				<?php esc_html_e( 'Config snippet', 'layrshift' ); ?>
			</button>
		</div>
	</header>

	<?php if ( $self_signed ) : ?>
		<div class="layrshift-studio-banner layrshift-studio-banner--warn">
			<span class="layrshift-studio-banner__icon" aria-hidden="true">⚠</span>
			<div>
				<strong><?php esc_html_e( 'Local TLS certificate', 'layrshift' ); ?></strong>
				<p><?php esc_html_e( 'Snippets include NODE_TLS_REJECT_UNAUTHORIZED=0 for self-signed dev hosts (.test, .local).', 'layrshift' ); ?></p>
			</div>
		</div>
	<?php endif; ?>

	<div class="layrshift-studio-panel is-active" data-studio-panel="agent" role="tabpanel" aria-labelledby="layrshift-studio-tab-agent">
		<div class="layrshift-studio-panel__toolbar">
			<span class="layrshift-studio-panel__label"><?php esc_html_e( 'Paste into your AI client', 'layrshift' ); ?></span>
			<button type="button" class="layrshift-btn layrshift-btn--primary layrshift-btn--small" id="layrshift-copy-agent-prompt">
				<?php esc_html_e( 'Copy prompt', 'layrshift' ); ?>
			</button>
		</div>
		<p class="layrshift-studio-note">
			<?php esc_html_e( 'Shares your application password with the agent. For private setup, switch to Config snippet.', 'layrshift' ); ?>
		</p>
		<pre class="layrshift-studio-code" id="layrshift-agent-prompt" tabindex="0"><?php echo esc_html( $paste_prompt ); ?></pre>
	</div>

	<div class="layrshift-studio-panel" data-studio-panel="snippet" role="tabpanel" aria-labelledby="layrshift-studio-tab-snippet" hidden>
		<div class="layrshift-snippet-layout">
			<aside class="layrshift-snippet-sidebar">
				<div class="layrshift-field">
					<label for="layrshift-client-select"><?php esc_html_e( 'AI client', 'layrshift' ); ?></label>
					<select id="layrshift-client-select" class="layrshift-select">
						<?php foreach ( $client_groups as $group ) : ?>
							<?php
							$group_clients = array_filter(
								$group['clients'],
								static fn( string $key ): bool => isset( $client_labels[ $key ] )
							);
							if ( array() === $group_clients ) {
								continue;
							}
							?>
							<optgroup label="<?php echo esc_attr( (string) $group['label'] ); ?>">
								<?php foreach ( $group_clients as $client_key ) : ?>
									<option value="<?php echo esc_attr( $client_key ); ?>"<?php selected( $client_key, $default_client ); ?>>
										<?php echo esc_html( (string) $client_labels[ $client_key ] ); ?>
									</option>
								<?php endforeach; ?>
							</optgroup>
						<?php endforeach; ?>
					</select>
				</div>

				<div class="layrshift-field">
					<label for="layrshift-mcp-name"><?php esc_html_e( 'Server name in config', 'layrshift' ); ?></label>
					<input type="text" id="layrshift-mcp-name" class="layrshift-input" value="<?php echo esc_attr( $server_name ); ?>" maxlength="25" />
					<p class="layrshift-field__help"><?php esc_html_e( 'Key used inside mcp.json — updates snippets live.', 'layrshift' ); ?></p>
				</div>

				<div class="layrshift-snippet-meta">
					<span class="layrshift-snippet-meta__label"><?php esc_html_e( 'Endpoint', 'layrshift' ); ?></span>
					<code class="layrshift-snippet-meta__value" id="layrshift-endpoint-url"><?php echo esc_html( $mcp_url ); ?></code>
				</div>

				<div class="layrshift-snippet-meta">
					<span class="layrshift-snippet-meta__label"><?php esc_html_e( 'Transport', 'layrshift' ); ?></span>
					<span class="layrshift-chip">npx @automattic/mcp-wordpress-remote</span>
				</div>

				<div class="layrshift-snippet-paths" id="layrshift-snippet-paths" hidden>
					<span class="layrshift-snippet-meta__label"><?php esc_html_e( 'Save to', 'layrshift' ); ?></span>
					<div class="layrshift-chip-row" id="layrshift-path-chips"></div>
				</div>

				<p class="layrshift-snippet-hint" id="layrshift-snippet-hint"></p>
			</aside>

			<div class="layrshift-snippet-main">
				<div class="layrshift-snippet-main__toolbar">
					<span class="layrshift-chip layrshift-chip--muted" id="layrshift-format-badge">JSON</span>
					<button type="button" class="layrshift-btn layrshift-btn--secondary layrshift-btn--small" id="layrshift-copy-snippet">
						<?php esc_html_e( 'Copy snippet', 'layrshift' ); ?>
					</button>
				</div>
				<pre class="layrshift-studio-code layrshift-studio-code--tall" id="layrshift-snippet-code" tabindex="0"></pre>
			</div>
		</div>
	</div>
</div>

<script type="application/json" id="layrshift-studio-data"><?php echo wp_json_encode( $studio_payload ); ?></script>
