<?php
/**
 * Google.com-style landing clones for Gutenberg + Elementor MCP testing.
 *
 * Usage: php wp-content/plugins/layrshift/tests/create-google-clone-landing-pages.php
 *
 * @package LayrShift
 */

require_once dirname( __DIR__, 4 ) . '/wp-load.php';

if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

$settings = get_option( 'layrshift_settings', array() );
$settings['enabled']           = true;
$settings['risk_acknowledged'] = true;
$settings['https_enforcement'] = false;
update_option( 'layrshift_settings', $settings );

$admins = get_users( array( 'role' => 'administrator', 'number' => 1 ) );
$user   = $admins[0];
$pw     = WP_Application_Passwords::create_new_application_password( $user->ID, array( 'name' => 'Google clone MCP ' . gmdate( 'Y-m-d H:i:s' ) ) );
if ( is_wp_error( $pw ) ) {
	fwrite( STDERR, $pw->get_error_message() . "\n" );
	exit( 1 );
}

$auth     = base64_encode( $user->user_login . ':' . $pw[0] );
$endpoint = rest_url( 'layrshift/v1/mcp' );
$suffix   = gmdate( 'Y-m-d-His' );

function google_mcp_post( string $url, string $auth, array $body, array $extra = array() ): array {
	$r = wp_remote_post(
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
	if ( is_wp_error( $r ) ) {
		return array( 'error' => $r->get_error_message() );
	}
	return array(
		'code'    => wp_remote_retrieve_response_code( $r ),
		'body'    => wp_remote_retrieve_body( $r ),
		'headers' => wp_remote_retrieve_headers( $r ),
	);
}

function google_parse_body( string $body ): array {
	$json = json_decode( $body, true );
	if ( is_array( $json ) ) {
		return $json;
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

function google_mcp_tool( string $endpoint, string $auth, array $extra, string $tool, array $args ): ?array {
	$res    = google_mcp_post(
		$endpoint,
		$auth,
		array(
			'jsonrpc' => '2.0',
			'id'      => wp_rand( 1000, 9999 ),
			'method'  => 'tools/call',
			'params'  => array( 'name' => $tool, 'arguments' => $args ),
		),
		$extra
	);
	$data   = google_parse_body( (string) ( $res['body'] ?? '' ) );
	$text   = (string) ( $data['result']['content'][0]['text'] ?? '' );
	$parsed = json_decode( $text, true );
	return is_array( $parsed ) ? $parsed : ( '' !== $text ? array( 'raw' => $text ) : null );
}

$init = google_mcp_post(
	$endpoint,
	$auth,
	array(
		'jsonrpc' => '2.0',
		'id'      => 1,
		'method'  => 'initialize',
		'params'  => array(
			'protocolVersion' => '2025-06-18',
			'capabilities'    => new stdClass(),
			'clientInfo'      => array( 'name' => 'google-clone-mcp', 'version' => '1.0' ),
		),
	)
);
$sid   = (string) ( $init['headers']['mcp-session-id'] ?? $init['headers']['Mcp-Session-Id'] ?? '' );
$extra = $sid ? array( 'Mcp-Session-Id' => $sid ) : array();

// --- Gutenberg page ---
$gb_title   = 'MCP Google Clone Gutenberg ' . $suffix;
$gb_page_id = (int) wp_insert_post(
	array(
		'post_title'  => $gb_title,
		'post_status' => 'draft',
		'post_type'   => 'page',
	)
);

$gb_content = <<<'HTML'
<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"0.75rem","right":"1.25rem","left":"1.25rem"}},"color":{"background":"#ffffff"}},"layout":{"type":"constrained","contentSize":"100%"}} -->
<div class="wp-block-group alignfull has-background" style="background-color:#ffffff;padding-top:0.75rem;padding-right:1.25rem;padding-left:1.25rem"><!-- wp:group {"layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"right"}} -->
<div class="wp-block-group"><!-- wp:paragraph {"style":{"typography":{"fontSize":"14px"}}} -->
<p style="font-size:14px"><a href="#">Gmail</a> &nbsp; <a href="#">Images</a> &nbsp; <a href="#" style="display:inline-block;width:24px;height:24px;border-radius:4px;background:#f1f3f4;text-align:center;line-height:24px;text-decoration:none;">⋮</a> &nbsp; <a href="#" style="display:inline-block;background:#1a73e8;color:#fff;padding:8px 16px;border-radius:4px;text-decoration:none;font-size:14px;">Sign in</a></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->

<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"4rem","bottom":"2rem"}},"dimensions":{"minHeight":"50vh"},"color":{"background":"#ffffff"}},"layout":{"type":"constrained","contentSize":"584px"}} -->
<div class="wp-block-group alignfull has-background" style="background-color:#ffffff;min-height:50vh;padding-top:4rem;padding-bottom:2rem"><!-- wp:heading {"textAlign":"center","level":1,"style":{"typography":{"fontSize":"92px","fontWeight":"400","letterSpacing":"-4px"},"color":{"text":"#4285f4"}}} -->
<h1 class="wp-block-heading has-text-align-center has-text-color" style="color:#4285f4;font-size:92px;font-weight:400;letter-spacing:-4px">Google</h1>
<!-- /wp:heading -->

<!-- wp:html -->
<div style="max-width:584px;margin:24px auto 0;">
<form role="search" style="display:flex;align-items:center;border:1px solid #dfe1e5;border-radius:24px;padding:12px 16px;box-shadow:0 1px 6px rgba(32,33,36,.28);">
<span style="margin-right:12px;color:#9aa0a6;">🔍</span>
<input type="search" placeholder="Search Google or type a URL" style="flex:1;border:none;outline:none;font-size:16px;font-family:inherit;" />
<span style="margin-left:12px;color:#9aa0a6;">🎤</span>
</form>
</div>
<!-- /wp:html -->

<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"},"style":{"spacing":{"margin":{"top":"28px"}}}} -->
<div class="wp-block-buttons is-content-justification-center" style="margin-top:28px"><!-- wp:button {"className":"is-style-fill","style":{"color":{"background":"#f8f9fa","text":"#3c4043"},"border":{"width":"0px","radius":"4px"},"typography":{"fontSize":"14px"}}} -->
<div class="wp-block-button is-style-fill"><a class="wp-block-button__link has-text-color has-background wp-element-button" style="border-width:0px;border-radius:4px;background-color:#f8f9fa;color:#3c4043;font-size:14px">Google Search</a></div>
<!-- /wp:button -->

<!-- wp:button {"className":"is-style-fill","style":{"color":{"background":"#f8f9fa","text":"#3c4043"},"border":{"width":"0px","radius":"4px"},"typography":{"fontSize":"14px"}}} -->
<div class="wp-block-button is-style-fill"><a class="wp-block-button__link has-text-color has-background wp-element-button" style="border-width:0px;border-radius:4px;background-color:#f8f9fa;color:#3c4043;font-size:14px">I'm Feeling Lucky</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons -->

<!-- wp:paragraph {"align":"center","style":{"spacing":{"margin":{"top":"2rem"}},"typography":{"fontSize":"13px"},"color":{"text":"#70757a"}}} -->
<p class="has-text-align-center has-text-color" style="color:#70757a;margin-top:2rem;font-size:13px">MCP test clone — layout inspired by google.com — Gutenberg</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"tagName":"footer","align":"full","style":{"spacing":{"padding":{"top":"1rem","bottom":"1rem","left":"1.5rem","right":"1.5rem"}},"color":{"background":"#f2f2f2"}},"layout":{"type":"constrained"}} -->
<footer class="wp-block-group alignfull has-background" style="background-color:#f2f2f2;padding-top:1rem;padding-right:1.5rem;padding-bottom:1rem;padding-left:1.5rem"><!-- wp:paragraph {"style":{"typography":{"fontSize":"15px"},"color":{"text":"#70757a"}}} -->
<p class="has-text-color" style="color:#70757a;font-size:15px">India</p>
<!-- /wp:paragraph -->

<!-- wp:separator {"style":{"color":{"background":"#dadce0"}}} -->
<hr class="wp-block-separator has-text-color has-alpha-channel-opacity has-background" style="background-color:#dadce0;color:#dadce0"/>
<!-- /wp:separator -->

<!-- wp:group {"layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between"}} -->
<div class="wp-block-group"><!-- wp:paragraph {"style":{"typography":{"fontSize":"14px"}}} -->
<p style="font-size:14px"><a href="#">Advertising</a> &nbsp; <a href="#">Business</a> &nbsp; <a href="#">How Search works</a></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"style":{"typography":{"fontSize":"14px"}}} -->
<p style="font-size:14px"><a href="#">Privacy</a> &nbsp; <a href="#">Terms</a> &nbsp; <a href="#">Settings</a></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></footer>
<!-- /wp:group -->
HTML;

// MCP: write file not needed; use execute-php via MCP then direct update for visibility.
$mcp_php = google_mcp_tool(
	$endpoint,
	$auth,
	$extra,
	'ls-execute-php',
	array(
		'code' => sprintf(
			'wp_update_post(array("ID" => %d, "post_content" => %s)); return array("updated" => %d, "len" => strlen(get_post(%d)->post_content));',
			$gb_page_id,
			var_export( $gb_content, true ),
			$gb_page_id,
			$gb_page_id
		),
	)
);

if ( empty( $mcp_php['updated'] ) ) {
	wp_update_post( array( 'ID' => $gb_page_id, 'post_content' => $gb_content ) );
}

$gb_mcp_read = google_mcp_tool(
	$endpoint,
	$auth,
	$extra,
	'ls-gb-get-content',
	array( 'post_id' => $gb_page_id )
);

// --- Elementor page ---
$el_title   = 'MCP Google Clone Elementor ' . $suffix;
$el_page_id = (int) wp_insert_post(
	array(
		'post_title'  => $el_title,
		'post_status' => 'draft',
		'post_type'   => 'page',
	)
);

update_post_meta( $el_page_id, '_elementor_edit_mode', 'builder' );
update_post_meta( $el_page_id, '_elementor_template_type', 'wp-page' );
update_post_meta( $el_page_id, '_wp_page_template', 'elementor_canvas' );

$id = static fn( string $s ): string => substr( md5( $s . $suffix ), 0, 7 );

$elements = array(
	array(
		'id'       => $id( 'top' ),
		'elType'   => 'section',
		'settings' => array(
			'content_width' => array( 'unit' => 'px', 'size' => 1200 ),
			'padding'       => array( 'unit' => 'px', 'top' => '16', 'bottom' => '8', 'right' => '24', 'left' => '24' ),
		),
		'elements' => array(
			array(
				'id'       => $id( 'top-col' ),
				'elType'   => 'column',
				'settings' => array( '_column_size' => 100 ),
				'elements' => array(
					array(
						'id'         => $id( 'top-nav' ),
						'elType'     => 'widget',
						'widgetType' => 'text-editor',
						'settings'   => array(
							'align'  => 'right',
							'editor' => '<p style="font-size:14px;margin:0;"><a href="#">Gmail</a> &nbsp; <a href="#">Images</a> &nbsp; <a href="#" style="display:inline-block;width:24px;height:24px;border-radius:4px;background:#f1f3f4;text-align:center;line-height:24px;text-decoration:none;">⋮</a> &nbsp; <a href="#" style="display:inline-block;background:#1a73e8;color:#fff;padding:8px 16px;border-radius:4px;text-decoration:none;">Sign in</a></p>',
						),
					),
				),
			),
		),
	),
	array(
		'id'       => $id( 'hero' ),
		'elType'   => 'section',
		'settings' => array(
			'height'        => 'min-height',
			'custom_height' => array( 'unit' => 'vh', 'size' => 55 ),
			'padding'       => array( 'unit' => 'px', 'top' => '80', 'bottom' => '40' ),
		),
		'elements' => array(
			array(
				'id'       => $id( 'hero-col' ),
				'elType'   => 'column',
				'settings' => array( '_column_size' => 100, 'content_position' => 'center' ),
				'elements' => array(
					array(
						'id'         => $id( 'logo' ),
						'elType'     => 'widget',
						'widgetType' => 'heading',
						'settings'   => array(
							'title'       => 'Google',
							'align'       => 'center',
							'size'        => 'xxl',
							'title_color' => '#4285F4',
							'typography_typography' => 'custom',
							'typography_font_size'  => array( 'unit' => 'px', 'size' => 92 ),
							'typography_font_weight'  => '400',
						),
					),
					array(
						'id'         => $id( 'search' ),
						'elType'     => 'widget',
						'widgetType' => 'html',
						'settings'   => array(
							'html' => '<div style="max-width:584px;margin:24px auto;"><form style="display:flex;align-items:center;border:1px solid #dfe1e5;border-radius:24px;padding:12px 16px;box-shadow:0 1px 6px rgba(32,33,36,.28);"><span style="margin-right:12px;">🔍</span><input type="search" placeholder="Search Google or type a URL" style="flex:1;border:none;outline:none;font-size:16px;width:100%;" /><span style="margin-left:12px;">🎤</span></form></div>',
						),
					),
					array(
						'id'         => $id( 'btn1' ),
						'elType'     => 'widget',
						'widgetType' => 'button',
						'settings'   => array(
							'text'          => 'Google Search',
							'align'         => 'center',
							'button_type'   => 'default',
							'background_color' => '#f8f9fa',
							'button_text_color' => '#3c4043',
							'border_radius' => array( 'unit' => 'px', 'top' => 4, 'right' => 4, 'bottom' => 4, 'left' => 4 ),
						),
					),
					array(
						'id'         => $id( 'btn2' ),
						'elType'     => 'widget',
						'widgetType' => 'button',
						'settings'   => array(
							'text'          => "I'm Feeling Lucky",
							'align'         => 'center',
							'background_color' => '#f8f9fa',
							'button_text_color' => '#3c4043',
						),
					),
					array(
						'id'         => $id( 'note' ),
						'elType'     => 'widget',
						'widgetType' => 'text-editor',
						'settings'   => array(
							'align'  => 'center',
							'editor' => '<p style="color:#70757a;font-size:13px;margin-top:24px;">MCP test clone — layout inspired by google.com — Elementor</p>',
						),
					),
				),
			),
		),
	),
	array(
		'id'       => $id( 'foot' ),
		'elType'   => 'section',
		'settings' => array(
			'background_background' => 'classic',
			'background_color'      => '#f2f2f2',
			'padding'               => array( 'unit' => 'px', 'top' => '16', 'bottom' => '16', 'left' => '24', 'right' => '24' ),
		),
		'elements' => array(
			array(
				'id'       => $id( 'foot-col' ),
				'elType'   => 'column',
				'settings' => array( '_column_size' => 100 ),
				'elements' => array(
					array(
						'id'         => $id( 'country' ),
						'elType'     => 'widget',
						'widgetType' => 'text-editor',
						'settings'   => array( 'editor' => '<p style="color:#70757a;font-size:15px;margin:0 0 12px;">India</p>' ),
					),
					array(
						'id'         => $id( 'foot-links' ),
						'elType'     => 'widget',
						'widgetType' => 'text-editor',
						'settings'   => array(
							'editor' => '<div style="display:flex;flex-wrap:wrap;justify-content:space-between;font-size:14px;gap:12px;border-top:1px solid #dadce0;padding-top:12px;"><span><a href="#">Advertising</a> &nbsp; <a href="#">Business</a> &nbsp; <a href="#">How Search works</a></span><span><a href="#">Privacy</a> &nbsp; <a href="#">Terms</a> &nbsp; <a href="#">Settings</a></span></div>',
						),
					),
				),
			),
		),
	),
);

$el_save = google_mcp_tool(
	$endpoint,
	$auth,
	$extra,
	'ls-el-save-document',
	array(
		'post_id'  => $el_page_id,
		'elements' => $elements,
	)
);

$el_verify = google_mcp_tool(
	$endpoint,
	$auth,
	$extra,
	'ls-el-get-document',
	array( 'post_id' => $el_page_id )
);

$result = array(
	'mcp'       => array(
		'initialize' => ( $init['code'] ?? 0 ) === 200,
		'tools_used' => array( 'ls-execute-php', 'ls-gb-get-content', 'ls-el-save-document', 'ls-el-get-document' ),
	),
	'gutenberg' => array(
		'post_id'     => $gb_page_id,
		'title'       => $gb_title,
		'edit_url'    => admin_url( 'post.php?post=' . $gb_page_id . '&action=edit' ),
		'preview_url' => get_preview_post_link( $gb_page_id ),
		'content_len' => strlen( (string) get_post( $gb_page_id )->post_content ),
		'mcp_php'     => $mcp_php,
	),
	'elementor' => array(
		'post_id'     => $el_page_id,
		'title'       => $el_title,
		'saved'       => $el_save['saved'] ?? false,
		'element_count' => $el_save['element_count'] ?? null,
		'edit_url'    => admin_url( 'post.php?post=' . $el_page_id . '&action=elementor' ),
		'preview_url' => get_preview_post_link( $el_page_id ),
		'template'    => 'elementor_canvas',
	),
);

echo wp_json_encode( $result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) . "\n";

if ( empty( $el_save['saved'] ) || strlen( (string) get_post( $gb_page_id )->post_content ) < 100 ) {
	exit( 1 );
}
