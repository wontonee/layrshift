<?php
/**
 * Create Gutenberg + Elementor test landing page drafts via LayrShift MCP.
 *
 * Usage: php wp-content/plugins/layrshift/tests/create-test-landing-pages.php
 *
 * @package LayrShift
 */

require_once dirname( __DIR__, 4 ) . '/wp-load.php';

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "Could not load WordPress.\n" );
	exit( 1 );
}

$settings = get_option( 'layrshift_settings', array() );
$settings['enabled']             = true;
$settings['risk_acknowledged']   = true;
$settings['https_enforcement']   = false;
update_option( 'layrshift_settings', $settings );

$admins = get_users( array( 'role' => 'administrator', 'number' => 1 ) );
if ( empty( $admins ) ) {
	fwrite( STDERR, "No administrator found.\n" );
	exit( 1 );
}

$user     = $admins[0];
$app_name = 'Landing Page Test ' . gmdate( 'Y-m-d H:i:s' );
$password = WP_Application_Passwords::create_new_application_password( $user->ID, array( 'name' => $app_name ) );
if ( is_wp_error( $password ) ) {
	fwrite( STDERR, $password->get_error_message() . "\n" );
	exit( 1 );
}

$auth     = base64_encode( $user->user_login . ':' . $password[0] );
$endpoint = rest_url( 'layrshift/v1/mcp' );
$suffix   = gmdate( 'Y-m-d-His' );

/**
 * @param array<string, mixed> $body
 * @return array<string, mixed>
 */
function landing_mcp_post( string $url, string $auth, array $body, array $extra = array() ): array {
	$response = wp_remote_post(
		$url,
		array(
			'headers'   => array_merge(
				array(
					'Authorization' => 'Basic ' . $auth,
					'Content-Type'  => 'application/json',
					'Accept'        => 'application/json, text/event-stream',
				),
				$extra
			),
			'body'      => wp_json_encode( $body ),
			'timeout'   => 120,
			'sslverify' => false,
		)
	);

	if ( is_wp_error( $response ) ) {
		return array( 'error' => $response->get_error_message() );
	}

	return array(
		'code' => wp_remote_retrieve_response_code( $response ),
		'body' => wp_remote_retrieve_body( $response ),
		'headers' => wp_remote_retrieve_headers( $response ),
	);
}

/**
 * @return array<string, mixed>
 */
function landing_parse_json_body( string $body ): array {
	$decoded = json_decode( $body, true );
	if ( is_array( $decoded ) ) {
		return $decoded;
	}
	foreach ( explode( "\n", $body ) as $line ) {
		$line = trim( $line );
		if ( str_starts_with( $line, 'data: ' ) ) {
			$chunk = json_decode( substr( $line, 6 ), true );
			if ( is_array( $chunk ) ) {
				return $chunk;
			}
		}
	}
	return array();
}

/**
 * @param array<string, mixed> $arguments
 * @return array<string, mixed>|null
 */
function landing_call_tool( string $endpoint, string $auth, array $extra, string $tool, array $arguments ): ?array {
	$response = landing_mcp_post(
		$endpoint,
		$auth,
		array(
			'jsonrpc' => '2.0',
			'id'      => wp_rand( 1000, 9999 ),
			'method'  => 'tools/call',
			'params'  => array(
				'name'      => $tool,
				'arguments' => $arguments,
			),
		),
		$extra
	);

	$parsed = landing_parse_json_body( (string) ( $response['body'] ?? '' ) );
	if ( ! empty( $parsed['error'] ) ) {
		fwrite( STDERR, "Tool {$tool} error: " . wp_json_encode( $parsed['error'] ) . "\n" );
		return null;
	}

	$text = (string) ( $parsed['result']['content'][0]['text'] ?? '' );
	if ( '' === $text ) {
		if ( ! empty( $parsed['result']['isError'] ) ) {
			fwrite( STDERR, "Tool {$tool} failed: " . wp_json_encode( $parsed['result'] ) . "\n" );
			return null;
		}
		return is_array( $parsed['result'] ?? null ) ? $parsed['result'] : null;
	}

	$data = json_decode( $text, true );
	return is_array( $data ) ? $data : array( 'raw' => $text );
}

$init = landing_mcp_post(
	$endpoint,
	$auth,
	array(
		'jsonrpc' => '2.0',
		'id'      => 1,
		'method'  => 'initialize',
		'params'  => array(
			'protocolVersion' => '2025-06-18',
			'capabilities'    => new stdClass(),
			'clientInfo'      => array( 'name' => 'landing-page-test', 'version' => '1.0.0' ),
		),
	)
);

if ( ( $init['code'] ?? 0 ) !== 200 ) {
	fwrite( STDERR, "MCP initialize failed.\n" );
	exit( 1 );
}

$session_id = (string) ( $init['headers']['mcp-session-id'] ?? $init['headers']['Mcp-Session-Id'] ?? '' );
$extra      = $session_id ? array( 'Mcp-Session-Id' => $session_id ) : array();

// --- Gutenberg landing ---
$gb_title   = 'Test Landing Gutenberg ' . $suffix;
$gb_page_id = wp_insert_post(
	array(
		'post_title'   => $gb_title,
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
			'style'  => array( 'spacing' => array( 'padding' => array( 'top' => '4rem', 'bottom' => '4rem' ) ) ),
		),
		'innerBlocks' => array(
			array(
				'name'       => 'core/heading',
				'attributes' => array(
					'level'   => 1,
					'content' => 'Transform your digital presence',
					'textAlign' => 'center',
				),
			),
			array(
				'name'       => 'core/paragraph',
				'attributes' => array(
					'align'   => 'center',
					'content' => 'We help agencies and SaaS teams ship polished WordPress experiences faster with LayrShift automation.',
				),
			),
			array(
				'name'       => 'core/spacer',
				'attributes' => array( 'height' => '32px' ),
			),
			array(
				'name'       => 'core/columns',
				'attributes' => array(),
				'innerBlocks' => array(
					array(
						'name'       => 'core/column',
						'attributes' => array(),
						'innerBlocks' => array(
							array( 'name' => 'core/heading', 'attributes' => array( 'level' => 3, 'content' => 'Strategy' ) ),
							array( 'name' => 'core/paragraph', 'attributes' => array( 'content' => 'Discovery workshops, positioning, and conversion-focused information architecture.' ) ),
						),
					),
					array(
						'name'       => 'core/column',
						'attributes' => array(),
						'innerBlocks' => array(
							array( 'name' => 'core/heading', 'attributes' => array( 'level' => 3, 'content' => 'Build' ) ),
							array( 'name' => 'core/paragraph', 'attributes' => array( 'content' => 'Gutenberg and Elementor workflows with MCP-driven content and layout automation.' ) ),
						),
					),
					array(
						'name'       => 'core/column',
						'attributes' => array(),
						'innerBlocks' => array(
							array( 'name' => 'core/heading', 'attributes' => array( 'level' => 3, 'content' => 'Grow' ) ),
							array( 'name' => 'core/paragraph', 'attributes' => array( 'content' => 'SEO, performance tuning, and ongoing optimization on your staging stack.' ) ),
						),
					),
				),
			),
			array(
				'name'       => 'core/spacer',
				'attributes' => array( 'height' => '24px' ),
			),
			array(
				'name'       => 'core/quote',
				'attributes' => array(
					'value'    => 'LayrShift cut our landing page turnaround from days to hours.',
					'citation' => '— Product lead, agency partner',
				),
			),
			array(
				'name'       => 'core/buttons',
				'attributes' => array( 'layout' => array( 'type' => 'flex', 'justifyContent' => 'center' ) ),
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
					'align'   => 'center',
					'content' => '<em>Draft test landing — Gutenberg queue — ' . esc_html( $suffix ) . '</em>',
				),
			),
		),
	),
);

$gb_queue = landing_call_tool(
	$endpoint,
	$auth,
	$extra,
	'ls-gb-add-pending-change',
	array(
		'label'      => 'Test Landing Gutenberg ' . $suffix,
		'target_id'  => $gb_page_id,
		'block_spec' => $gb_block_spec,
		'agent_label' => 'landing-page-test',
	)
);

$gb_batch_id = (int) ( $gb_queue['batch_id'] ?? 0 );
if ( $gb_batch_id > 0 ) {
	landing_call_tool( $endpoint, $auth, $extra, 'ls-gb-enable-batch-finalization', array( 'batch_id' => $gb_batch_id ) );
}

// --- Elementor landing ---
$el_title   = 'Test Landing Elementor ' . $suffix;
$el_page_id = wp_insert_post(
	array(
		'post_title'  => $el_title,
		'post_status' => 'draft',
		'post_type'   => 'page',
	)
);

update_post_meta( $el_page_id, '_elementor_edit_mode', 'builder' );
update_post_meta( $el_page_id, '_elementor_template_type', 'wp-page' );

$uid = static fn( string $seed ): string => substr( md5( $seed . $suffix ), 0, 7 );

$hero_section = $uid( 'hero-sec' );
$hero_col     = $uid( 'hero-col' );
$feat_section = $uid( 'feat-sec' );
$feat_col1    = $uid( 'feat-c1' );
$feat_col2    = $uid( 'feat-c2' );
$feat_col3    = $uid( 'feat-c3' );
$cta_section  = $uid( 'cta-sec' );
$cta_col      = $uid( 'cta-col' );

$elementor_elements = array(
	array(
		'id'       => $hero_section,
		'elType'   => 'section',
		'settings' => array(
			'content_width' => array( 'unit' => 'px', 'size' => 1140 ),
			'padding'       => array( 'unit' => 'px', 'top' => '96', 'bottom' => '64' ),
		),
		'elements' => array(
			array(
				'id'       => $hero_col,
				'elType'   => 'column',
				'settings' => array( '_column_size' => 100 ),
				'elements' => array(
					array(
						'id'         => $uid( 'hero-h' ),
						'elType'     => 'widget',
						'widgetType' => 'heading',
						'settings'   => array(
							'title' => 'Launch faster with confidence',
							'align' => 'center',
							'size'  => 'xl',
						),
					),
					array(
						'id'         => $uid( 'hero-t' ),
						'elType'     => 'widget',
						'widgetType' => 'text-editor',
						'settings'   => array(
							'editor' => '<p style="text-align:center;font-size:18px;">Professional Elementor landing page built via LayrShift MCP for integration testing.</p>',
						),
					),
				),
			),
		),
	),
	array(
		'id'       => $feat_section,
		'elType'   => 'section',
		'settings' => array(
			'padding' => array( 'unit' => 'px', 'top' => '48', 'bottom' => '48' ),
		),
		'elements' => array(
			array(
				'id'       => $feat_col1,
				'elType'   => 'column',
				'settings' => array( '_column_size' => 33 ),
				'elements' => array(
					array(
						'id'         => $uid( 'f1-h' ),
						'elType'     => 'widget',
						'widgetType' => 'heading',
						'settings'   => array( 'title' => 'Design', 'size' => 'medium' ),
					),
					array(
						'id'         => $uid( 'f1-t' ),
						'elType'     => 'widget',
						'widgetType' => 'text-editor',
						'settings'   => array( 'editor' => '<p>Pixel-perfect sections with Elementor widgets and global styles.</p>' ),
					),
				),
			),
			array(
				'id'       => $feat_col2,
				'elType'   => 'column',
				'settings' => array( '_column_size' => 33 ),
				'elements' => array(
					array(
						'id'         => $uid( 'f2-h' ),
						'elType'     => 'widget',
						'widgetType' => 'heading',
						'settings'   => array( 'title' => 'Automate', 'size' => 'medium' ),
					),
					array(
						'id'         => $uid( 'f2-t' ),
						'elType'     => 'widget',
						'widgetType' => 'text-editor',
						'settings'   => array( 'editor' => '<p>MCP abilities draft and update documents without manual JSON surgery.</p>' ),
					),
				),
			),
			array(
				'id'       => $feat_col3,
				'elType'   => 'column',
				'settings' => array( '_column_size' => 33 ),
				'elements' => array(
					array(
						'id'         => $uid( 'f3-h' ),
						'elType'     => 'widget',
						'widgetType' => 'heading',
						'settings'   => array( 'title' => 'Ship', 'size' => 'medium' ),
					),
					array(
						'id'         => $uid( 'f3-t' ),
						'elType'     => 'widget',
						'widgetType' => 'text-editor',
						'settings'   => array( 'editor' => '<p>Preview on staging, iterate with agents, publish when ready.</p>' ),
					),
				),
			),
		),
	),
	array(
		'id'       => $cta_section,
		'elType'   => 'section',
		'settings' => array(
			'padding' => array( 'unit' => 'px', 'top' => '64', 'bottom' => '96' ),
		),
		'elements' => array(
			array(
				'id'       => $cta_col,
				'elType'   => 'column',
				'settings' => array( '_column_size' => 100 ),
				'elements' => array(
					array(
						'id'         => $uid( 'quote' ),
						'elType'     => 'widget',
						'widgetType' => 'text-editor',
						'settings'   => array(
							'editor' => '<blockquote><p>“Our team ships client landings same-day now.”</p><cite>— Studio founder</cite></blockquote>',
						),
					),
					array(
						'id'         => $uid( 'cta-btn' ),
						'elType'     => 'widget',
						'widgetType' => 'button',
						'settings'   => array(
							'text'  => 'Start your project',
							'align' => 'center',
							'link'  => array( 'url' => '#contact' ),
						),
					),
					array(
						'id'         => $uid( 'footer' ),
						'elType'     => 'widget',
						'widgetType' => 'text-editor',
						'settings'   => array(
							'editor' => '<p style="text-align:center;"><em>Draft test landing — Elementor — ' . esc_html( $suffix ) . '</em></p>',
						),
					),
				),
			),
		),
	),
);

$el_save = landing_call_tool(
	$endpoint,
	$auth,
	$extra,
	'ls-el-save-document',
	array(
		'post_id'  => $el_page_id,
		'elements' => $elementor_elements,
	)
);

$el_verify = landing_call_tool(
	$endpoint,
	$auth,
	$extra,
	'ls-el-get-document',
	array( 'post_id' => $el_page_id )
);

$result = array(
	'gutenberg' => array(
		'post_id'          => $gb_page_id,
		'title'            => $gb_title,
		'status'           => get_post_status( $gb_page_id ),
		'batch_id'         => $gb_batch_id,
		'batch_status'     => $gb_queue['batch_status'] ?? null,
		'edit_url'         => admin_url( 'post.php?post=' . $gb_page_id . '&action=edit' ),
		'finalizer_url'    => admin_url( 'admin.php?page=layrshift-gutenberg-finalize' ),
		'queued'           => $gb_batch_id > 0,
	),
	'elementor' => array(
		'post_id'       => $el_page_id,
		'title'         => $el_title,
		'status'        => get_post_status( $el_page_id ),
		'saved'         => $el_save['saved'] ?? false,
		'element_count' => $el_verify['elements'] ?? ( $el_save['element_count'] ?? null ),
		'edit_url'      => admin_url( 'post.php?post=' . $el_page_id . '&action=elementor' ),
		'preview_url'   => get_preview_post_link( $el_page_id ),
	),
);

echo wp_json_encode( $result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) . "\n";

if ( $gb_batch_id <= 0 || empty( $el_save['saved'] ) ) {
	exit( 1 );
}
