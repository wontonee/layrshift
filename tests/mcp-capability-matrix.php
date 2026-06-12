<?php
/**
 * LayrShift MCP capability matrix runner (run from site root).
 *
 * Usage: php wp-content/plugins/layrshift/tests/mcp-capability-matrix.php [--json]
 *
 * @package LayrShift
 */

require_once dirname( __DIR__, 4 ) . '/wp-load.php';

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "Could not load WordPress.\n" );
	exit( 1 );
}

$json_output = in_array( '--json', $argv ?? array(), true );

// Pre-flight: ensure abilities can run on local HTTP.
$settings = get_option( 'layrshift_settings', array() );
$settings['enabled']           = true;
$settings['risk_acknowledged']   = true;
$settings['https_enforcement']   = false;
update_option( 'layrshift_settings', $settings );

$admins = get_users( array( 'role' => 'administrator', 'number' => 1 ) );
if ( empty( $admins ) ) {
	fwrite( STDERR, "No administrator user found.\n" );
	exit( 1 );
}

$user     = $admins[0];
$app_name = 'LayrShift Capability Matrix ' . gmdate( 'Y-m-d H:i:s' );
$password = WP_Application_Passwords::create_new_application_password(
	$user->ID,
	array( 'name' => $app_name )
);

if ( is_wp_error( $password ) ) {
	fwrite( STDERR, 'App password error: ' . $password->get_error_message() . "\n" );
	exit( 1 );
}

$app_password = $password[0];
$auth         = base64_encode( $user->user_login . ':' . $app_password );
$endpoint     = rest_url( 'layrshift/v1/mcp' );

$results = array(
	'endpoint'      => $endpoint,
	'user'          => $user->user_login,
	'timestamp'     => gmdate( 'c' ),
	'tests'         => array(),
	'landing_pages' => array(),
	'summary'       => array( 'pass' => 0, 'fail' => 0, 'skip' => 0, 'na' => 0 ),
);

/**
 * @param array<string, mixed> $body
 * @return array<string, mixed>
 */
function layrshift_mcp_request( string $url, string $auth, array $body, array $extra_headers = array() ): array {
	$headers = array_merge(
		array(
			'Authorization' => 'Basic ' . $auth,
			'Content-Type'  => 'application/json',
			'Accept'        => 'application/json, text/event-stream',
		),
		$extra_headers
	);

	$response = wp_remote_post(
		$url,
		array(
			'headers'   => $headers,
			'body'      => wp_json_encode( $body ),
			'timeout'   => 120,
			'sslverify' => false,
		)
	);

	if ( is_wp_error( $response ) ) {
		return array( 'error' => $response->get_error_message() );
	}

	return array(
		'code'    => wp_remote_retrieve_response_code( $response ),
		'headers' => wp_remote_retrieve_headers( $response ),
		'body'    => wp_remote_retrieve_body( $response ),
	);
}

/**
 * @return array<string, mixed>
 */
function layrshift_parse_sse_body( string $body ): array {
	$decoded = array();
	foreach ( explode( "\n", $body ) as $line ) {
		$line = trim( $line );
		if ( str_starts_with( $line, 'data: ' ) ) {
			$chunk = json_decode( substr( $line, 6 ), true );
			if ( is_array( $chunk ) ) {
				$decoded = $chunk;
			}
		}
	}
	if ( array() !== $decoded ) {
		return $decoded;
	}

	$json = json_decode( $body, true );
	return is_array( $json ) ? $json : array();
}

/**
 * @param array<string, mixed> $results
 */
function layrshift_record_test( array &$results, string $ability, string $input_summary, string $status, string $notes = '' ): void {
	$results['tests'][] = array(
		'ability'       => $ability,
		'input_summary' => $input_summary,
		'result'        => $status,
		'notes'         => $notes,
	);
	$key = match ( $status ) {
		'PASS' => 'pass',
		'FAIL' => 'fail',
		'SKIP' => 'skip',
		default => 'na',
	};
	++$results['summary'][ $key ];
}

$session_id = '';

$init = layrshift_mcp_request(
	$endpoint,
	$auth,
	array(
		'jsonrpc' => '2.0',
		'id'      => 1,
		'method'  => 'initialize',
		'params'  => array(
			'protocolVersion' => '2025-06-18',
			'capabilities'    => new stdClass(),
			'clientInfo'      => array(
				'name'    => 'ls-capability-matrix',
				'version' => '1.0.0',
			),
		),
	)
);

$init_ok = ( $init['code'] ?? 0 ) === 200;
layrshift_record_test(
	$results,
	'MCP initialize',
	'protocolVersion 2025-06-18',
	$init_ok ? 'PASS' : 'FAIL',
	'HTTP ' . ( $init['code'] ?? '?' ) . ( ! empty( $init['error'] ) ? ' — ' . $init['error'] : '' )
);

if ( ! empty( $init['headers']['mcp-session-id'] ) ) {
	$session_id = (string) $init['headers']['mcp-session-id'];
} elseif ( ! empty( $init['headers']['Mcp-Session-Id'] ) ) {
	$session_id = (string) $init['headers']['Mcp-Session-Id'];
}

$extra = $session_id ? array( 'Mcp-Session-Id' => $session_id ) : array();

/**
 * @param array<string, mixed> $arguments
 * @return array<string, mixed>
 */
function layrshift_call_tool( string $endpoint, string $auth, array $extra, string $tool_name, array $arguments, int $id ): array {
	$response = layrshift_mcp_request(
		$endpoint,
		$auth,
		array(
			'jsonrpc' => '2.0',
			'id'      => $id,
			'method'  => 'tools/call',
			'params'  => array(
				'name'      => $tool_name,
				'arguments' => $arguments,
			),
		),
		$extra
	);

	$parsed = layrshift_parse_sse_body( (string) ( $response['body'] ?? '' ) );
	return array(
		'http'   => $response['code'] ?? 0,
		'raw'    => $response,
		'parsed' => $parsed,
	);
}

$call_id = 10;

/**
 * @param array<string, mixed> $results
 * @return array<string, mixed>
 */
function layrshift_test_tool( array &$results, string $endpoint, string $auth, array $extra, string $tool, array $args, string $input_summary, int &$call_id, bool $expect_error = false ): array {
	global $call_id;
	$out    = layrshift_call_tool( $endpoint, $auth, $extra, $tool, $args, $call_id++ );
	$parsed = $out['parsed'];
	$ok     = ( $out['http'] === 200 ) && empty( $parsed['error'] );

	if ( ! $ok && isset( $parsed['result']['isError'] ) && false === $parsed['result']['isError'] ) {
		$ok = true;
	}

	if ( $ok && isset( $parsed['result']['content'][0]['text'] ) ) {
		$inner = json_decode( (string) $parsed['result']['content'][0]['text'], true );
		if ( is_array( $inner ) && isset( $inner['code'] ) && str_contains( (string) $inner['code'], 'error' ) ) {
			$ok = false;
		}
	}

	if ( $expect_error ) {
		$ok = ! $ok;
	}

	$note = 'HTTP ' . ( $out['http'] ?? '?' );
	if ( ! empty( $parsed['error'] ) ) {
		$note .= ' — ' . wp_json_encode( $parsed['error'] );
	} elseif ( ! empty( $parsed['result']['content'][0]['text'] ) ) {
		$note .= ' — ' . substr( (string) $parsed['result']['content'][0]['text'], 0, 200 );
	}

	layrshift_record_test( $results, $tool, $input_summary, $ok ? 'PASS' : 'FAIL', $note );
	return $out;
}

// tools/list (MCP surface)
$tools_list_response = layrshift_mcp_request(
	$endpoint,
	$auth,
	array(
		'jsonrpc' => '2.0',
		'id'      => 2,
		'method'  => 'tools/list',
		'params'  => new stdClass(),
	),
	$extra
);
$tools_list_data = json_decode( (string) ( $tools_list_response['body'] ?? '' ), true );
$mcp_tool_names    = array_column( (array) ( $tools_list_data['result']['tools'] ?? array() ), 'name' );
$mcp_tool_count    = count( $mcp_tool_names );

layrshift_record_test(
	$results,
	'tools/list',
	'count public MCP tools',
	$mcp_tool_count > 0 ? 'PASS' : 'FAIL',
	'tool_count=' . $mcp_tool_count
);

// mcp-adapter abilities (registered but not exposed as MCP tools on this endpoint)
wp_set_current_user( $user->ID );
$discover_ability = wp_get_ability( 'mcp-adapter/discover-abilities' );
$discover_data    = $discover_ability ? $discover_ability->execute() : array();
$discover_count   = is_array( $discover_data['abilities'] ?? null ) ? count( $discover_data['abilities'] ) : 0;

layrshift_record_test(
	$results,
	'mcp-adapter/discover-abilities',
	'abilities API (direct)',
	$discover_count > 0 ? 'PASS' : 'FAIL',
	'abilities=' . $discover_count . '; not in tools/list=' . ( in_array( 'mcp-adapter-discover-abilities', $mcp_tool_names, true ) ? 'no' : 'yes' )
);

layrshift_record_test(
	$results,
	'mcp-adapter/discover-abilities (instructions)',
	'layrshift_instructions present',
	! empty( $discover_data['layrshift_instructions'] ) ? 'PASS' : 'FAIL',
	'instructions_len=' . strlen( (string) ( $discover_data['layrshift_instructions'] ?? '' ) )
);

$info_ability = wp_get_ability( 'mcp-adapter/get-ability-info' );
$info_ok      = false;
if ( $info_ability ) {
	$info_result = $info_ability->execute( array( 'ability_name' => 'layrshift/read-file' ) );
	$info_ok     = ! is_wp_error( $info_result ) && ! empty( $info_result );
}
layrshift_record_test( $results, 'mcp-adapter/get-ability-info', 'read-file schema', $info_ok ? 'PASS' : 'FAIL', 'direct ability execution' );

$exec_adapter = wp_get_ability( 'mcp-adapter/execute-ability' );
$exec_ok      = false;
if ( $exec_adapter ) {
	$exec_result = $exec_adapter->execute(
		array(
			'ability_name' => 'layrshift/execute-php',
			'parameters'   => array( 'code' => 'return get_bloginfo("name");' ),
		)
	);
	$exec_ok = ! is_wp_error( $exec_result );
}
layrshift_record_test( $results, 'mcp-adapter/execute-ability', 'execute-php via adapter', $exec_ok ? 'PASS' : 'FAIL', 'direct ability execution' );

// Filesystem
$theme_rel = 'wp-content/themes/it-landing-theme';
layrshift_test_tool( $results, $endpoint, $auth, $extra, 'ls-list-directory', array( 'path' => $theme_rel ), 'theme root', $call_id );
layrshift_test_tool( $results, $endpoint, $auth, $extra, 'ls-read-file', array( 'path' => $theme_rel . '/style.css' ), 'style.css header', $call_id );

$sandbox_file = 'wp-content/layrshift-sandbox/mcp-matrix-test.txt';
layrshift_test_tool( $results, $endpoint, $auth, $extra, 'ls-write-file', array( 'path' => $sandbox_file, 'content' => "MCP matrix test\n" ), 'write sandbox txt', $call_id );
layrshift_test_tool( $results, $endpoint, $auth, $extra, 'ls-edit-file', array( 'path' => $sandbox_file, 'old_string' => 'matrix', 'new_string' => 'MATRIX' ), 'edit sandbox txt', $call_id );
layrshift_test_tool( $results, $endpoint, $auth, $extra, 'ls-delete-file', array( 'path' => $sandbox_file ), 'delete sandbox txt', $call_id );

$sandbox_php = 'wp-content/layrshift-sandbox/mcp-matrix-probe.php';
layrshift_test_tool( $results, $endpoint, $auth, $extra, 'ls-write-file', array( 'path' => $sandbox_php, 'content' => "<?php\n// MCP matrix probe\nreturn 'ok';\n" ), 'write sandbox php', $call_id );
layrshift_test_tool( $results, $endpoint, $auth, $extra, 'ls-disable-file', array( 'filename' => 'mcp-matrix-probe.php' ), 'disable sandbox php', $call_id );
layrshift_test_tool( $results, $endpoint, $auth, $extra, 'ls-enable-file', array( 'filename' => 'mcp-matrix-probe.php' ), 'enable sandbox php', $call_id );
layrshift_test_tool( $results, $endpoint, $auth, $extra, 'ls-delete-file', array( 'path' => $sandbox_php ), 'cleanup sandbox php', $call_id );

layrshift_test_tool(
	$results,
	$endpoint,
	$auth,
	$extra,
	'ls-execute-php',
	array( 'code' => 'return array("blog" => get_bloginfo("name"), "plugins" => count(get_option("active_plugins", array())));' ),
	'active plugins probe',
	$call_id
);

layrshift_test_tool( $results, $endpoint, $auth, $extra, 'ls-create-upload-link', array(), 'upload link', $call_id );
layrshift_test_tool( $results, $endpoint, $auth, $extra, 'ls-create-admin-access-link', array(), 'admin access link', $call_id );
layrshift_test_tool( $results, $endpoint, $auth, $extra, 'ls-run-wp-cli', array( 'args' => array( 'option', 'get', 'blogname' ) ), 'wp option get blogname', $call_id );

// Skills
layrshift_test_tool( $results, $endpoint, $auth, $extra, 'ls-skill-get', array( 'slug' => 'wordpress-dev' ), 'wordpress-dev skill', $call_id );
layrshift_test_tool( $results, $endpoint, $auth, $extra, 'ls-skill-get', array( 'slug' => 'elementor' ), 'elementor skill', $call_id );
layrshift_test_tool( $results, $endpoint, $auth, $extra, 'ls-skill-get', array( 'slug' => 'divi' ), 'divi skill', $call_id );

$test_skill_slug = 'mcp-matrix-ephemeral-' . gmdate( 'Ymd' );
layrshift_test_tool(
	$results,
	$endpoint,
	$auth,
	$extra,
	'ls-skill-write',
	array(
		'slug'        => $test_skill_slug,
		'name'        => 'MCP Matrix Ephemeral',
		'description' => 'Temporary skill for capability matrix testing.',
		'content'     => "# Ephemeral\n\nTest skill.\n",
	),
	'create ephemeral skill',
	$call_id
);
layrshift_test_tool(
	$results,
	$endpoint,
	$auth,
	$extra,
	'ls-skill-edit',
	array(
		'slug'        => $test_skill_slug,
		'description' => 'Updated ephemeral skill description.',
	),
	'patch skill description',
	$call_id
);
layrshift_test_tool( $results, $endpoint, $auth, $extra, 'ls-skill-delete', array( 'slug' => $test_skill_slug ), 'delete ephemeral skill', $call_id );

// Gutenberg
layrshift_test_tool( $results, $endpoint, $auth, $extra, 'ls-gb-get-finalizer-runtime', array(), 'finalizer runtime', $call_id );

$batch_out = layrshift_test_tool(
	$results,
	$endpoint,
	$auth,
	$extra,
	'ls-gb-create-pending-batch',
	array(
		'label'       => 'MCP Matrix Gutenberg Test',
		'agent_label' => 'capability-matrix',
	),
	'create draft batch',
	$call_id
);

$batch_id = 0;
if ( ! empty( $batch_out['parsed']['result']['content'][0]['text'] ) ) {
	$batch_data = json_decode( (string) $batch_out['parsed']['result']['content'][0]['text'], true );
	$batch_id   = (int) ( $batch_data['batch_id'] ?? 0 );
}

// Create Gutenberg landing page draft (unique slug per run)
$gb_suffix  = gmdate( 'Y-m-d-His' );
$gb_page_id = wp_insert_post(
	array(
		'post_title'   => 'MCP Gutenberg Landing ' . $gb_suffix,
		'post_status'  => 'draft',
		'post_type'    => 'page',
		'post_content' => '',
	)
);

$gb_block_spec = array(
	array(
		'name'       => 'core/group',
		'attributes' => array(
			'layout' => array( 'type' => 'constrained' ),
		),
		'innerBlocks' => array(
			array(
				'name'       => 'core/heading',
				'attributes' => array(
					'level'   => 1,
					'content' => 'Ship faster with LayrShift',
				),
			),
			array(
				'name'       => 'core/paragraph',
				'attributes' => array(
					'content' => 'Professional agency landing page built via MCP Gutenberg queue.',
				),
			),
			array(
				'name'       => 'core/columns',
				'attributes' => array(),
				'innerBlocks' => array(
					array(
						'name'       => 'core/column',
						'attributes' => array(),
						'innerBlocks' => array(
							array(
								'name'       => 'core/heading',
								'attributes' => array( 'level' => 3, 'content' => 'Strategy' ),
							),
							array(
								'name'       => 'core/paragraph',
								'attributes' => array( 'content' => 'Discovery, positioning, and conversion-focused UX.' ),
							),
						),
					),
					array(
						'name'       => 'core/column',
						'attributes' => array(),
						'innerBlocks' => array(
							array(
								'name'       => 'core/heading',
								'attributes' => array( 'level' => 3, 'content' => 'Delivery' ),
							),
							array(
								'name'       => 'core/paragraph',
								'attributes' => array( 'content' => 'Gutenberg and Elementor workflows with MCP automation.' ),
							),
						),
					),
					array(
						'name'       => 'core/column',
						'attributes' => array(),
						'innerBlocks' => array(
							array(
								'name'       => 'core/heading',
								'attributes' => array( 'level' => 3, 'content' => 'Support' ),
							),
							array(
								'name'       => 'core/paragraph',
								'attributes' => array( 'content' => 'Ongoing optimization, SEO, and performance tuning.' ),
							),
						),
					),
				),
			),
			array(
				'name'       => 'core/quote',
				'attributes' => array(
					'value'   => 'LayrShift turned our WordPress backlog into a same-day launch pipeline.',
					'citation' => '— Agency client',
				),
			),
			array(
				'name'       => 'core/buttons',
				'attributes' => array(),
				'innerBlocks' => array(
					array(
						'name'       => 'core/button',
						'attributes' => array(
							'text' => 'Book a discovery call',
							'url'  => '#contact',
						),
					),
				),
			),
			array(
				'name'       => 'core/paragraph',
				'attributes' => array(
					'content' => '<em>Draft landing page generated by MCP capability matrix — ' . gmdate( 'Y-m-d' ) . '</em>',
				),
			),
		),
	),
);

$landing_batch_out = layrshift_call_tool(
	$endpoint,
	$auth,
	$extra,
	'ls-gb-add-pending-change',
	array(
		'label'      => 'MCP Gutenberg Landing ' . $gb_suffix,
		'target_id'  => $gb_page_id,
		'block_spec' => $gb_block_spec,
	),
	$call_id++
);

$landing_batch_id = $batch_id;
$landing_note     = '';
if ( ! empty( $landing_batch_out['parsed']['result']['content'][0]['text'] ) ) {
	$landing_text = (string) $landing_batch_out['parsed']['result']['content'][0]['text'];
	$landing_batch_data = json_decode( $landing_text, true );
	if ( is_array( $landing_batch_data ) ) {
		$landing_batch_id = (int) ( $landing_batch_data['batch_id'] ?? $batch_id );
		$landing_note     = substr( $landing_text, 0, 200 );
	} elseif ( preg_match( '/batch #(\d+)/', $landing_text, $matches ) ) {
		$landing_batch_id = (int) $matches[1];
		$landing_note     = $landing_text;
	}
}

layrshift_record_test(
	$results,
	'ls-gb-add-pending-change',
	'hero + features + CTA on draft page ' . $gb_page_id,
	$landing_batch_id > 0 ? 'PASS' : 'FAIL',
	'batch_id=' . $landing_batch_id . ( $landing_note ? ' — ' . $landing_note : '' )
);

if ( $landing_batch_id > 0 ) {
	layrshift_test_tool( $results, $endpoint, $auth, $extra, 'ls-gb-enable-batch-finalization', array( 'batch_id' => $landing_batch_id ), 'enable finalization', $call_id );
	layrshift_test_tool( $results, $endpoint, $auth, $extra, 'ls-gb-get-pending-batch', array( 'batch_id' => $landing_batch_id ), 'read batch state', $call_id );
	layrshift_test_tool( $results, $endpoint, $auth, $extra, 'ls-gb-get-finalization-url', array( 'batch_id' => $landing_batch_id ), 'finalization URL', $call_id );
}

layrshift_test_tool( $results, $endpoint, $auth, $extra, 'ls-gb-list-pending-batches', array( 'limit' => 5 ), 'list batches', $call_id );
layrshift_test_tool( $results, $endpoint, $auth, $extra, 'ls-gb-get-content', array( 'post_id' => $gb_page_id ), 'read page content tree', $call_id );

$results['landing_pages']['gutenberg'] = array(
	'post_id'  => $gb_page_id,
	'title'    => get_the_title( $gb_page_id ),
	'edit_url' => admin_url( 'post.php?post=' . $gb_page_id . '&action=edit' ),
	'batch_id' => $landing_batch_id,
	'path'     => 'gutenberg queue (Template Studio unavailable — no Gemini key)',
);

// Elementor landing page
$el_page_id = wp_insert_post(
	array(
		'post_title'  => 'MCP Elementor Landing ' . $gb_suffix,
		'post_status' => 'draft',
		'post_type'   => 'page',
	)
);

update_post_meta( $el_page_id, '_elementor_edit_mode', 'builder' );
update_post_meta( $el_page_id, '_elementor_template_type', 'wp-page' );

$section_id = substr( md5( 'sec-' . $el_page_id ), 0, 7 );
$col_id     = substr( md5( 'col-' . $el_page_id ), 0, 7 );
$h_id       = substr( md5( 'h-' . $el_page_id ), 0, 7 );
$t_id       = substr( md5( 't-' . $el_page_id ), 0, 7 );
$b_id       = substr( md5( 'b-' . $el_page_id ), 0, 7 );

$elementor_elements = array(
	array(
		'id'       => $section_id,
		'elType'   => 'section',
		'settings' => array(
			'content_width' => array( 'unit' => 'px', 'size' => 1140 ),
			'padding'       => array( 'unit' => 'px', 'top' => '80', 'bottom' => '80' ),
		),
		'elements' => array(
			array(
				'id'       => $col_id,
				'elType'   => 'column',
				'settings' => array( '_column_size' => 100 ),
				'elements' => array(
					array(
						'id'         => $h_id,
						'elType'     => 'widget',
						'widgetType' => 'heading',
						'settings'   => array(
							'title' => 'Build beautiful WordPress experiences',
							'align' => 'center',
							'size'  => 'xl',
						),
					),
					array(
						'id'         => $t_id,
						'elType'     => 'widget',
						'widgetType' => 'text-editor',
						'settings'   => array(
							'editor' => '<p style="text-align:center;">Elementor landing page drafted via LayrShift MCP elementor-save-document.</p>',
						),
					),
					array(
						'id'         => $b_id,
						'elType'     => 'widget',
						'widgetType' => 'button',
						'settings'   => array(
							'text' => 'Start your project',
							'align' => 'center',
							'link' => array( 'url' => '#contact' ),
						),
					),
				),
			),
		),
	),
);

layrshift_test_tool( $results, $endpoint, $auth, $extra, 'ls-el-list-templates', array(), 'list templates', $call_id );
layrshift_test_tool( $results, $endpoint, $auth, $extra, 'ls-el-get-document', array( 'post_id' => $el_page_id ), 'empty draft document', $call_id );

$el_save = layrshift_test_tool(
	$results,
	$endpoint,
	$auth,
	$extra,
	'ls-el-save-document',
	array(
		'post_id'  => $el_page_id,
		'elements' => $elementor_elements,
	),
	'hero section on draft page ' . $el_page_id,
	$call_id
);

layrshift_test_tool( $results, $endpoint, $auth, $extra, 'ls-el-get-document', array( 'post_id' => $el_page_id ), 'verify saved tree', $call_id );

$results['landing_pages']['elementor'] = array(
	'post_id'       => $el_page_id,
	'title'         => get_the_title( $el_page_id ),
	'edit_url'      => admin_url( 'post.php?post=' . $el_page_id . '&action=elementor' ),
	'preview_url'   => get_preview_post_link( $el_page_id ),
	'element_count' => 5,
);

// Plugin integrations (conditional)
if ( defined( 'WPSEO_VERSION' ) ) {
	layrshift_test_tool( $results, $endpoint, $auth, $extra, 'ls-yoast-get-site-settings', array(), 'yoast site settings', $call_id );
} else {
	layrshift_record_test( $results, 'ls-yoast-*', 'Yoast inactive', 'N/A', 'Plugin not active' );
}

if ( class_exists( 'WP_Smush' ) || defined( 'WP_SMUSH_VERSION' ) ) {
	layrshift_test_tool( $results, $endpoint, $auth, $extra, 'ls-smush-get-stats', array(), 'smush stats', $call_id );
} else {
	layrshift_record_test( $results, 'ls-smush-*', 'Smush inactive', 'N/A', 'Plugin not active' );
}

if ( defined( 'VAULTSHIFT_VERSION' ) ) {
	layrshift_test_tool( $results, $endpoint, $auth, $extra, 'ls-vs-get-status', array(), 'vaultshift status', $call_id );
	layrshift_test_tool( $results, $endpoint, $auth, $extra, 'ls-vs-list-activity', array( 'limit' => 5 ), 'vaultshift activity', $call_id );
} else {
	layrshift_record_test( $results, 'ls-vs-*', 'VaultShift inactive', 'N/A', 'Plugin not active' );
}

if ( defined( 'BLOGIBOT_VERSION' ) ) {
	layrshift_test_tool( $results, $endpoint, $auth, $extra, 'ls-blogibot-get-status', array(), 'blogibot status', $call_id );
} else {
	layrshift_record_test( $results, 'ls-blogibot-*', 'BlogiBot inactive', 'N/A', 'Plugin not active' );
}

// Abilities Hub policy: disable list-directory, verify absent from fresh tools/list, re-enable
update_option( 'layrshift_ability_rules', array( 'layrshift/list-directory' => array( 'disabled' => true ) ) );

$hub_disable_probe = layrshift_mcp_request(
	$endpoint,
	$auth,
	array(
		'jsonrpc' => '2.0',
		'id'      => 9001,
		'method'  => 'initialize',
		'params'  => array(
			'protocolVersion' => '2025-06-18',
			'capabilities'    => new stdClass(),
			'clientInfo'      => array( 'name' => 'hub-policy', 'version' => '1' ),
		),
	)
);
$hub_disable_sid = (string) ( $hub_disable_probe['headers']['mcp-session-id'] ?? $hub_disable_probe['headers']['Mcp-Session-Id'] ?? '' );
$hub_disable_extra = $hub_disable_sid ? array( 'Mcp-Session-Id' => $hub_disable_sid ) : array();
$hub_disable_tools = layrshift_mcp_request(
	$endpoint,
	$auth,
	array( 'jsonrpc' => '2.0', 'id' => 9002, 'method' => 'tools/list', 'params' => new stdClass() ),
	$hub_disable_extra
);
$hub_disable_data = json_decode( (string) ( $hub_disable_tools['body'] ?? '' ), true );
$hub_disable_names = array_column( (array) ( $hub_disable_data['result']['tools'] ?? array() ), 'name' );
$disabled_ok = ! in_array( 'ls-list-directory', $hub_disable_names, true );
layrshift_record_test( $results, 'Abilities Hub policy', 'disable list-directory', $disabled_ok ? 'PASS' : 'FAIL', 'absent from tools/list on fresh session; count=' . count( $hub_disable_names ) );

update_option( 'layrshift_ability_rules', array() );

$hub_enable_probe = layrshift_mcp_request(
	$endpoint,
	$auth,
	array(
		'jsonrpc' => '2.0',
		'id'      => 9003,
		'method'  => 'initialize',
		'params'  => array(
			'protocolVersion' => '2025-06-18',
			'capabilities'    => new stdClass(),
			'clientInfo'      => array( 'name' => 'hub-policy', 'version' => '1' ),
		),
	)
);
$hub_enable_sid = (string) ( $hub_enable_probe['headers']['mcp-session-id'] ?? $hub_enable_probe['headers']['Mcp-Session-Id'] ?? '' );
$hub_enable_extra = $hub_enable_sid ? array( 'Mcp-Session-Id' => $hub_enable_sid ) : array();
$hub_enable_tools = layrshift_mcp_request(
	$endpoint,
	$auth,
	array( 'jsonrpc' => '2.0', 'id' => 9004, 'method' => 'tools/list', 'params' => new stdClass() ),
	$hub_enable_extra
);
$hub_enable_data = json_decode( (string) ( $hub_enable_tools['body'] ?? '' ), true );
$hub_enable_names = array_column( (array) ( $hub_enable_data['result']['tools'] ?? array() ), 'name' );
$enabled_ok = in_array( 'ls-list-directory', $hub_enable_names, true );
layrshift_record_test( $results, 'Abilities Hub policy', 're-enable list-directory', $enabled_ok ? 'PASS' : 'FAIL', 'restored in tools/list; count=' . count( $hub_enable_names ) );

// Cleanup test batch if created separately
if ( $batch_id > 0 && $batch_id !== $landing_batch_id ) {
	layrshift_test_tool( $results, $endpoint, $auth, $extra, 'ls-gb-delete-pending-batch', array( 'batch_id' => $batch_id ), 'cleanup test batch', $call_id );
}

$results['discover_ability_count'] = $discover_count;
$results['mcp_tool_count']         = $mcp_tool_count;
$results['abilities_status']       = \LayrShift\Plugin::get_abilities_status();

if ( $json_output ) {
	echo wp_json_encode( $results, JSON_PRETTY_PRINT ) . "\n";
} else {
	echo "LayrShift MCP Capability Matrix\n";
	echo "Endpoint: {$endpoint}\n";
	echo "Summary: pass={$results['summary']['pass']} fail={$results['summary']['fail']} skip={$results['summary']['skip']} na={$results['summary']['na']}\n\n";
	foreach ( $results['tests'] as $test ) {
		echo sprintf( "[%s] %s — %s\n", $test['result'], $test['ability'], $test['notes'] );
	}
	echo "\nLanding pages:\n";
	echo wp_json_encode( $results['landing_pages'], JSON_PRETTY_PRINT ) . "\n";
}

file_put_contents(
	dirname( __DIR__ ) . '/tests/mcp-capability-matrix-results.json',
	wp_json_encode( $results, JSON_PRETTY_PRINT )
);
