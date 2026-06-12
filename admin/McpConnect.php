<?php
/**
 * MCP tab — application password creation and config building.
 *
 * @package LayrShift
 */

declare( strict_types=1 );

namespace LayrShift\Admin;

final class McpConnect {

	private const PASSWORD_PREFIX = 'LayrShift';

	private const NAME_PLACEHOLDER = '__LAYRSHIFT_MCP_NAME__';

	private const PW_SLOT = '__LAYRSHIFT_PW_SLOT__';

	/**
	 * @return void
	 */
	public static function init(): void {
		add_action( 'admin_init', array( self::class, 'handle_admin_init' ) );
	}

	/**
	 * @return void
	 */
	public static function handle_admin_init(): void {
		if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$page = sanitize_key( (string) ( $_GET['page'] ?? '' ) );
		if ( ! Admin::is_app_page( $page ) ) {
			return;
		}

		if ( isset( $_POST['layrshift_revoke_password'] ) ) {
			self::handle_revoke();
		}
	}

	/**
	 * Build all context needed by the MCP tab view.
	 *
	 * @return array<string, mixed>
	 */
	public static function get_tab_context( string $mcp_url ): array {
		$user     = wp_get_current_user();
		$username = $user->user_login;

		$create_result    = self::handle_create();
		$create_error     = is_wp_error( $create_result ) ? $create_result : null;
		$new_password     = is_string( $create_result ) ? $create_result : null;

		$existing_result   = self::handle_use_existing();
		$existing_error    = is_wp_error( $existing_result ) ? $existing_result : null;
		$existing_password = is_string( $existing_result ) ? $existing_result : null;

		$display_password = $new_password ?? $existing_password ?? 'YOUR-APP-PASSWORD';
		$server_name      = self::get_mcp_server_name_default();

		return array(
			'username'               => $username,
			'display_password'       => $display_password,
			'password_is_placeholder' => 'YOUR-APP-PASSWORD' === $display_password,
			'new_password'           => $new_password,
			'existing_password'      => $existing_password,
			'create_error'           => $create_error,
			'existing_error'         => $existing_error,
			'revoked'                => 'revoked' === sanitize_key( (string) ( $_GET['layrshift_result'] ?? '' ) ),
			'password_status'        => self::get_password_status(),
			'mcp_passwords'          => self::get_layrshift_passwords(),
			'mcp_url'                => $mcp_url,
			'mcp_server_name'        => $server_name,
			'name_placeholder'       => self::NAME_PLACEHOLDER,
			'pw_slot'                => self::PW_SLOT,
			'self_signed_https'      => self::likely_self_signed_https(),
			'client_labels'          => self::get_client_labels(),
			'client_groups'          => self::get_client_groups(),
			'client_configs'         => self::build_configs( $mcp_url, $username, $display_password, self::NAME_PLACEHOLDER ),
			'mcp_config'             => self::build_mcp_config_json( $mcp_url, $username, $display_password, $server_name ),
			'setup_prompt'           => self::build_setup_prompt( $mcp_url, $username, $display_password, $server_name ),
			'paste_prompt'           => self::build_paste_to_agent_paragraph( $mcp_url, $username, $display_password, $server_name ),
			'paste_prompt_template'  => self::build_paste_to_agent_paragraph( $mcp_url, $username, $display_password, self::NAME_PLACEHOLDER, self::PW_SLOT ),
		);
	}

	/**
	 * @return array<string, string>
	 */
	/**
	 * Client picker groups for the connection studio (dropdown optgroups).
	 *
	 * @return array<string, array{label: string, clients: list<string>}>
	 */
	public static function get_client_groups(): array {
		return array(
			'ides'     => array(
				'label'   => __( 'IDEs & editors', 'layrshift' ),
				'clients' => array( 'cursor', 'vscode', 'windsurf', 'zed', 'github-copilot' ),
			),
			'cli'      => array(
				'label'   => __( 'CLI & agents', 'layrshift' ),
				'clients' => array( 'claude-code', 'codex', 'gemini-cli', 'opencode' ),
			),
			'desktop'  => array(
				'label'   => __( 'Desktop apps', 'layrshift' ),
				'clients' => array( 'claude-desktop', 'antigravity' ),
			),
			'extensions' => array(
				'label'   => __( 'Editor extensions', 'layrshift' ),
				'clients' => array( 'cline', 'roo-code', 'kilo-code', 'amazon-q' ),
			),
		);
	}

	public static function get_client_labels(): array {
		return array(
			'claude-code'    => 'Claude Code',
			'claude-desktop' => 'Claude Desktop',
			'codex'          => 'Codex',
			'antigravity'    => 'Antigravity',
			'cursor'         => 'Cursor',
			'vscode'         => 'VS Code',
			'github-copilot' => 'GitHub Copilot',
			'windsurf'       => 'Windsurf',
			'cline'          => 'Cline',
			'gemini-cli'     => 'Gemini CLI',
			'roo-code'       => 'Roo Code',
			'amazon-q'       => 'Amazon Q',
			'zed'            => 'Zed',
			'kilo-code'      => 'Kilo Code',
			'opencode'       => 'OpenCode',
		);
	}

	public static function get_mcp_server_name_default(): string {
		/** @var string $site_host */
		$site_host = wp_parse_url( home_url(), PHP_URL_HOST ) ?? 'wordpress';
		$site_host = (string) preg_replace( '/^www\./', '', $site_host );
		$label     = strtolower( (string) ( explode( '.', $site_host )[0] ?? 'wp' ) );
		$label     = (string) preg_replace( '/[^a-z0-9-]+/', '-', $label );
		$label     = trim( $label, '-' );
		$label     = substr( $label, 0, 8 );
		$label     = rtrim( $label, '-' );

		if ( '' === $label ) {
			$label = 'wp';
		}

		return 'ls-' . $label;
	}

	public static function likely_self_signed_https(): bool {
		$home = home_url();
		if ( ! str_starts_with( strtolower( $home ), 'https://' ) ) {
			return false;
		}

		$host = strtolower( (string) wp_parse_url( $home, PHP_URL_HOST ) );
		if ( '' === $host ) {
			return false;
		}

		/** @var array<int, string> $patterns */
		$patterns = apply_filters(
			'layrshift_self_signed_host_patterns',
			array( '.local', '.test', 'localhost', '.lndo.site', '.ddev.site' )
		);

		foreach ( $patterns as $needle ) {
			if ( '' !== $needle && str_contains( $host, $needle ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @return array{command: string, args: list<string>, env: array<string, string>}
	 */
	public static function build_npx_server( string $mcp_url, string $username, string $password ): array {
		$env = array(
			'WP_API_URL'       => $mcp_url,
			'WP_API_USERNAME'  => $username,
			'WP_API_PASSWORD'  => $password,
		);

		if ( self::likely_self_signed_https() ) {
			$env['NODE_TLS_REJECT_UNAUTHORIZED'] = '0';
		}

		return array(
			'command' => 'npx',
			'args'    => array( '-y', '@automattic/mcp-wordpress-remote@latest' ),
			'env'     => $env,
		);
	}

	public static function build_mcp_config_json( string $mcp_url, string $username, string $password, string $server_name ): string {
		$npx_server = self::build_npx_server( $mcp_url, $username, $password );

		return (string) wp_json_encode(
			array(
				'mcpServers' => array(
					$server_name => $npx_server,
				),
			),
			JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
		);
	}

	public static function build_setup_prompt( string $mcp_url, string $username, string $password, string $server_name ): string {
		return self::build_paste_to_agent_paragraph( $mcp_url, $username, $password, $server_name );
	}

	public static function build_paste_to_agent_paragraph(
		string $mcp_url,
		string $username,
		string $display_password,
		string $server_name,
		?string $password_placeholder = null
	): string {
		$password_value = $password_placeholder ?? $display_password;

		$lines = array(
			'I want to add this WordPress site as an MCP server to this AI client.',
			'',
			'Connection details:',
			'- Server URL: ' . $mcp_url,
			'- Username: ' . $username,
			'- Application password: ' . $password_value,
			'- Server name to use in the config: ' . $server_name,
			'- Transport: @automattic/mcp-wordpress-remote via npx',
			'',
			'Setup rules:',
			'- Pass credentials ONLY as env vars: WP_API_URL, WP_API_USERNAME, WP_API_PASSWORD. Do NOT use CLI flags like --url or --password (the package ignores them).',
			'- args array must be exactly ["-y", "@automattic/mcp-wordpress-remote@latest"].',
		);

		if ( self::likely_self_signed_https() ) {
			$lines[] = '- Also set NODE_TLS_REJECT_UNAUTHORIZED="0" in env (this site uses a local self-signed TLS certificate).';
		}

		$lines = array_merge(
			$lines,
			array(
				'',
				'Don\'t ask me to confirm choices already specified above. After writing the config, restart or reload the MCP session (most clients require it), then verify by listing the server\'s tools. If it fails, show me the stderr from the npx process before proposing changes.',
				'',
				'If you cannot modify the config of this AI client from here, tell me to expand "Configure manually for your client" on the LayrShift MCP page and copy the snippet myself.',
			)
		);

		return implode( "\n", $lines );
	}

	/**
	 * @return array<string, array{code: string, hint: string, paths: array<string, string>, isShell: bool}>
	 */
	public static function build_configs( string $mcp_url, string $username, string $display_password, string $mcp_name ): array {
		$opts             = JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES;
		$npx_server       = self::build_npx_server( $mcp_url, $username, $display_password );
		$mcp_servers_json = (string) wp_json_encode( array( 'mcpServers' => array( $mcp_name => $npx_server ) ), $opts );
		$vscode_json      = (string) wp_json_encode( array( 'servers' => array( $mcp_name => $npx_server ) ), $opts );

		/* translators: %s: config file name wrapped in <code> tags */
		$add_to = __( 'Add to %s.', 'layrshift' );

		$special = array(
			'claude-code' => array(
				'code'    => self::build_claude_code_cmd( $mcp_name, $mcp_url, $username, $display_password ),
				'hint'    => __( 'Run in your terminal.', 'layrshift' ),
				'paths'   => array(),
				'isShell' => true,
			),
			'codex'       => array(
				'code'    => self::build_codex_toml( $mcp_name, $mcp_url, $username, $display_password ),
				'hint'    => sprintf( $add_to, '<code>config.toml</code>' ),
				'paths'   => array(
					'macOS / Linux' => '~/.codex/config.toml',
					'Windows'       => '%USERPROFILE%\\.codex\\config.toml',
				),
				'isShell' => false,
			),
			'zed'         => array(
				'code'    => self::build_zed_json( $mcp_name, $npx_server, $opts ),
				'hint'    => sprintf( $add_to, '<code>settings.json</code>' ),
				'paths'   => array( 'macOS / Linux' => '~/.config/zed/settings.json' ),
				'isShell' => false,
			),
			'opencode'    => array(
				'code'    => self::build_opencode_json( $mcp_name, $mcp_url, $username, $display_password, $opts ),
				'hint'    => sprintf( $add_to, '<code>opencode.json</code>' ),
				'paths'   => array(
					__( 'Project', 'layrshift' ) => 'opencode.json',
					__( 'Global', 'layrshift' )  => '~/.config/opencode/opencode.json',
				),
				'isShell' => false,
			),
		);

		return array_merge( self::build_standard_configs( $mcp_servers_json, $vscode_json, $add_to ), $special );
	}

	/**
	 * @return array<string, array{code: string, hint: string, paths: array<string, string>, isShell: bool}>
	 */
	private static function build_standard_configs( string $mcp_servers_json, string $vscode_json, string $add_to ): array {
		return array(
			'claude-desktop' => array(
				'code'    => $mcp_servers_json,
				'hint'    => sprintf( $add_to, '<code>claude_desktop_config.json</code>' ),
				'paths'   => array(
					'macOS'   => '~/Library/Application Support/Claude/claude_desktop_config.json',
					'Windows' => '%APPDATA%\\Claude\\claude_desktop_config.json',
				),
				'isShell' => false,
			),
			'cursor'         => array(
				'code'    => $mcp_servers_json,
				'hint'    => sprintf( $add_to, '<code>mcp.json</code>' ),
				'paths'   => array(
					__( 'Global', 'layrshift' )  => '~/.cursor/mcp.json',
					__( 'Project', 'layrshift' ) => '.cursor/mcp.json',
				),
				'isShell' => false,
			),
			'vscode'         => array(
				'code'    => $vscode_json,
				'hint'    => sprintf( $add_to, '<code>mcp.json</code>' ),
				'paths'   => array(
					__( 'Workspace', 'layrshift' ) => '.vscode/mcp.json',
					__( 'User', 'layrshift' )    => __( 'Run: MCP: Open User Configuration (command palette)', 'layrshift' ),
				),
				'isShell' => false,
			),
			'windsurf'       => array(
				'code'    => $mcp_servers_json,
				'hint'    => sprintf( $add_to, '<code>mcp_config.json</code>' ),
				'paths'   => array(
					'macOS / Linux' => '~/.codeium/windsurf/mcp_config.json',
					'Windows'       => '%USERPROFILE%\\.codeium\\windsurf\\mcp_config.json',
				),
				'isShell' => false,
			),
			'cline'          => array(
				'code'    => $mcp_servers_json,
				'hint'    => sprintf( $add_to, '<code>cline_mcp_settings.json</code>' ),
				'paths'   => array(
					__( 'Via UI', 'layrshift' ) => __( 'Cline sidebar → MCP Servers → Configure MCP Servers', 'layrshift' ),
				),
				'isShell' => false,
			),
			'roo-code'       => array(
				'code'    => $mcp_servers_json,
				'hint'    => sprintf( $add_to, '<code>mcp.json</code>' ),
				'paths'   => array(
					__( 'Project', 'layrshift' ) => '.roo/mcp.json',
					__( 'Via UI', 'layrshift' )  => __( 'Roo Code sidebar → MCP Servers → Configure MCP Servers', 'layrshift' ),
				),
				'isShell' => false,
			),
			'kilo-code'      => array(
				'code'    => $mcp_servers_json,
				'hint'    => sprintf( $add_to, '<code>mcp.json</code>' ),
				'paths'   => array(
					__( 'Project', 'layrshift' ) => '.kilocode/mcp.json',
					__( 'Via UI', 'layrshift' )  => __( 'Kilo Code sidebar → MCP Servers → Configure MCP Servers', 'layrshift' ),
				),
				'isShell' => false,
			),
			'github-copilot' => array(
				'code'    => $vscode_json,
				'hint'    => sprintf( $add_to, '<code>mcp.json</code>' ),
				'paths'   => array(
					__( 'Project', 'layrshift' ) => '.github/copilot/mcp.json',
				),
				'isShell' => false,
			),
			'amazon-q'       => array(
				'code'    => $mcp_servers_json,
				'hint'    => sprintf( $add_to, '<code>mcp.json</code>' ),
				'paths'   => array(
					__( 'Global', 'layrshift' )  => '~/.aws/amazonq/mcp.json',
					__( 'Project', 'layrshift' ) => '.amazonq/mcp.json',
				),
				'isShell' => false,
			),
			'gemini-cli'     => array(
				'code'    => $mcp_servers_json,
				'hint'    => sprintf( $add_to, '<code>settings.json</code>' ),
				'paths'   => array(
					__( 'Global', 'layrshift' )  => '~/.gemini/settings.json',
					__( 'Project', 'layrshift' ) => '.gemini/settings.json',
				),
				'isShell' => false,
			),
			'antigravity'    => array(
				'code'    => $mcp_servers_json,
				'hint'    => sprintf( $add_to, '<code>mcp_config.json</code>' ),
				'paths'   => array(
					'macOS / Linux' => '~/.gemini/antigravity/mcp_config.json',
					'Windows'       => '%USERPROFILE%\\.gemini\\antigravity\\mcp_config.json',
				),
				'isShell' => false,
			),
		);
	}

	/**
	 * @param array{command: string, args: list<string>, env: array<string, string>} $npx_server
	 */
	private static function build_zed_json( string $mcp_name, array $npx_server, int $opts ): string {
		return (string) wp_json_encode(
			array(
				'context_servers' => array(
					$mcp_name => array_merge(
						array(
							'source'  => 'custom',
							'enabled' => true,
						),
						$npx_server
					),
				),
			),
			$opts
		);
	}

	private static function build_opencode_json( string $mcp_name, string $mcp_url, string $username, string $display_password, int $opts ): string {
		$environment = array(
			'WP_API_URL'      => $mcp_url,
			'WP_API_USERNAME' => $username,
			'WP_API_PASSWORD' => $display_password,
		);
		if ( self::likely_self_signed_https() ) {
			$environment['NODE_TLS_REJECT_UNAUTHORIZED'] = '0';
		}

		return (string) wp_json_encode(
			array(
				'mcp' => array(
					$mcp_name => array(
						'type'        => 'local',
						'command'     => array( 'npx', '-y', '@automattic/mcp-wordpress-remote@latest' ),
						'environment' => $environment,
					),
				),
			),
			$opts
		);
	}

	private static function build_codex_toml( string $mcp_name, string $mcp_url, string $username, string $display_password ): string {
		$esc = static fn( string $v ): string => '"' . str_replace( array( '\\', '"' ), array( '\\\\', '\\"' ), $v ) . '"';

		$lines = array(
			'[mcp_servers.' . $mcp_name . ']',
			'command = "npx"',
			'args = ["-y", "@automattic/mcp-wordpress-remote@latest"]',
			'',
			'[mcp_servers.' . $mcp_name . '.env]',
			'WP_API_URL = ' . $esc( $mcp_url ),
			'WP_API_USERNAME = ' . $esc( $username ),
			'WP_API_PASSWORD = ' . $esc( $display_password ),
		);

		if ( self::likely_self_signed_https() ) {
			$lines[] = 'NODE_TLS_REJECT_UNAUTHORIZED = "0"';
		}

		return implode( "\n", $lines );
	}

	private static function build_claude_code_cmd( string $mcp_name, string $mcp_url, string $username, string $display_password ): string {
		$sq = static fn( string $v ): string => "'" . str_replace( "'", "'\\''", $v ) . "'";

		$parts = array(
			'claude mcp add ' . $sq( $mcp_name ),
			'--env WP_API_URL=' . $sq( $mcp_url ),
			'--env WP_API_USERNAME=' . $sq( $username ),
			'--env WP_API_PASSWORD=' . $sq( $display_password ),
		);

		if ( self::likely_self_signed_https() ) {
			$parts[] = '--env NODE_TLS_REJECT_UNAUTHORIZED=' . $sq( '0' );
		}

		$parts[] = '-- npx -y @automattic/mcp-wordpress-remote@latest';

		return implode( " \\\n  ", $parts );
	}

	/**
	 * @return array{available: bool, reason: string, message: string}
	 */
	public static function get_password_status(): array {
		if ( wp_is_application_passwords_available() ) {
			return array(
				'available' => true,
				'reason'    => 'available',
				'message'   => '',
			);
		}

		if ( ! wp_is_application_passwords_supported() ) {
			return array(
				'available' => false,
				'reason'    => 'unsupported',
				'message'   => __( 'Application Passwords require HTTPS or WP_ENVIRONMENT_TYPE set to "local".', 'layrshift' ),
			);
		}

		return array(
			'available' => false,
			'reason'    => 'filtered',
			'message'   => __( 'Application Passwords have been disabled on this site, likely by a security plugin. Re-enable them in your security plugin settings to continue.', 'layrshift' ),
		);
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_layrshift_passwords(): array {
		if ( ! class_exists( \WP_Application_Passwords::class ) ) {
			return array();
		}

		$user_id = get_current_user_id();
		$all     = \WP_Application_Passwords::get_user_application_passwords( $user_id );

		return array_values(
			array_filter(
				$all,
				static function ( array $item ): bool {
					$name = (string) ( $item['name'] ?? '' );
					return str_starts_with( $name, self::PASSWORD_PREFIX );
				}
			)
		);
	}

	/**
	 * @return string|\WP_Error|null
	 */
	private static function handle_create() {
		if ( null === ( $_POST['layrshift_create_password'] ?? null ) ) {
			return null;
		}

		check_admin_referer( 'layrshift_create_password' );

		$status = self::get_password_status();
		if ( ! $status['available'] ) {
			return new \WP_Error( 'not_available', $status['message'] );
		}

		$user_id  = get_current_user_id();
		$raw_name = isset( $_POST['layrshift_password_name'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['layrshift_password_name'] ) ) : '';
		$app_name = '' !== $raw_name ? self::PASSWORD_PREFIX . ': ' . $raw_name : self::PASSWORD_PREFIX;
		$existing = \WP_Application_Passwords::get_user_application_passwords( $user_id );
		$names    = array_column( $existing, 'name' );

		if ( in_array( $app_name, $names, true ) ) {
			$i = 2;
			while ( in_array( $app_name . ' ' . $i, $names, true ) ) {
				++$i;
			}
			$app_name = $app_name . ' ' . $i;
		}

		$result = \WP_Application_Passwords::create_new_application_password( $user_id, array( 'name' => $app_name ) );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return $result[0];
	}

	/**
	 * @return string|\WP_Error|null
	 */
	private static function handle_use_existing() {
		if ( null === ( $_POST['layrshift_use_existing_password'] ?? null ) ) {
			return null;
		}

		check_admin_referer( 'layrshift_use_existing_password' );

		$value = isset( $_POST['layrshift_existing_password'] ) ? trim( (string) wp_unslash( $_POST['layrshift_existing_password'] ) ) : '';
		if ( '' === $value ) {
			return new \WP_Error( 'empty', __( 'Paste the application password value before submitting.', 'layrshift' ) );
		}

		if ( strlen( $value ) < 16 ) {
			return new \WP_Error(
				'too_short',
				__( 'That does not look like an application password. WordPress application passwords are at least 16 characters long.', 'layrshift' )
			);
		}

		return $value;
	}

	/**
	 * @return void
	 */
	private static function handle_revoke(): void {
		$uuid = isset( $_POST['layrshift_revoke_uuid'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['layrshift_revoke_uuid'] ) ) : '';
		if ( '' === $uuid ) {
			return;
		}

		check_admin_referer( 'layrshift_revoke_password_' . $uuid );

		\WP_Application_Passwords::delete_application_password( get_current_user_id(), $uuid );

		wp_safe_redirect( admin_url( 'admin.php?page=' . Admin::APP_PAGE . '&tab=mcp&layrshift_result=revoked' ) );
		exit;
	}
}
