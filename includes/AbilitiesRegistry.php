<?php
/**
 * Registers LayrShift abilities with the Abilities API.
 *
 * @package LayrShift
 */

declare( strict_types=1 );

namespace LayrShift;

use LayrShift\Abilities\CreateAdminAccessLink;
use LayrShift\AbilityCategories;
use LayrShift\Abilities\CreateUploadLink;
use LayrShift\Abilities\DeleteFile;
use LayrShift\Abilities\DisableFile;
use LayrShift\Abilities\EditFile;
use LayrShift\Abilities\EnableFile;
use LayrShift\Abilities\ListDirectory;
use LayrShift\Abilities\ReadFile;
use LayrShift\Abilities\WriteFile;
use LayrShift\Blogibot\Loader as BlogibotLoader;
use LayrShift\Elementor\Loader as ElementorLoader;
use LayrShift\Gutenberg\Loader as GutenbergLoader;
use LayrShift\Skills\Prompts;
use LayrShift\Smush\Loader as SmushLoader;
use LayrShift\VaultShift\Loader as VaultShiftLoader;
use LayrShift\Yoast\Loader as YoastLoader;

/**
 * Ability registry.
 */
final class AbilitiesRegistry {

	public static function register(): void {
		if ( ! Plugin::meets_requirements() || ! Plugin::is_abilities_enabled() ) {
			return;
		}

		$abilities = self::definitions();
		foreach ( $abilities as $name => $config ) {
			if ( wp_has_ability( $name ) ) {
				continue;
			}
			wp_register_ability( $name, $config );
		}

		CreateAdminAccessLink::register();
		if ( class_exists( 'LayrShift\\Abilities\\RunWpCli' ) ) {
			\LayrShift\Abilities\RunWpCli::register();
		}
		GutenbergLoader::register_abilities();
		ElementorLoader::register_abilities();
		YoastLoader::register_abilities();
		SmushLoader::register_abilities();
		VaultShiftLoader::register_abilities();
		BlogibotLoader::register_abilities();
	}

	/**
	 * @return array<string, array<string, mixed>>
	 */
	public static function tool_names(): array {
		$wp_cli = class_exists( 'LayrShift\\Abilities\\RunWpCli' )
			? array( 'layrshift/run-wp-cli', 'layrshift/get-wp-cli-job' )
			: array();

		return array_merge(
			array_keys( self::definitions() ),
			array( 'layrshift/create-admin-access-link' ),
			$wp_cli,
			GutenbergLoader::ability_names(),
			ElementorLoader::ability_names(),
			YoastLoader::ability_names(),
			SmushLoader::ability_names(),
			VaultShiftLoader::ability_names(),
			BlogibotLoader::ability_names(),
			array(
				'layrshift/skill-get',
				'layrshift/skill-write',
				'layrshift/skill-edit',
				'layrshift/skill-delete',
			)
		);
	}

	/**
	 * @return list<string>
	 */
	public static function prompt_names(): array {
		return Prompts::ability_names();
	}

	/**
	 * @return array<string, array<string, mixed>>
	 */
	private static function definitions(): array {
		$mcp_tool = static fn( string $description ): array => array(
			'public' => true,
			'type'   => 'tool',
		);

		$definitions = array(
			'layrshift/read-file' => array(
				'label'               => __( 'Read File', 'layrshift' ),
				'description'         => __( 'Read a file from the server filesystem.', 'layrshift' ),
				'category'            => AbilityCategories::FILESYSTEM,
				'execute_callback'    => array( ReadFile::class, 'execute' ),
				'permission_callback' => array( Auth::class, 'check_ability_permission' ),
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'path'       => array( 'type' => 'string' ),
						'start_line' => array( 'type' => 'integer' ),
						'end_line'   => array( 'type' => 'integer' ),
					),
					'required'   => array( 'path' ),
				),
				'meta'                => array(
					'mcp'         => $mcp_tool( 'read' ),
					'annotations' => array( 'readOnly' => true ),
				),
			),
			'layrshift/write-file' => array(
				'label'               => __( 'Write File', 'layrshift' ),
				'description'         => __( 'Create or overwrite a file. PHP files are restricted to the sandbox directory.', 'layrshift' ),
				'category'            => AbilityCategories::FILESYSTEM,
				'execute_callback'    => array( WriteFile::class, 'execute' ),
				'permission_callback' => array( Auth::class, 'check_ability_permission' ),
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'path'     => array( 'type' => 'string' ),
						'content'  => array( 'type' => 'string' ),
						'encoding' => array( 'type' => 'string', 'enum' => array( 'utf8', 'base64' ) ),
					),
					'required'   => array( 'path', 'content' ),
				),
				'meta'                => array(
					'mcp'         => $mcp_tool( 'write' ),
					'annotations' => array( 'destructive' => true ),
				),
			),
			'layrshift/edit-file' => array(
				'label'               => __( 'Edit File', 'layrshift' ),
				'description'         => __( 'Make a precise string replacement in an existing file.', 'layrshift' ),
				'category'            => AbilityCategories::FILESYSTEM,
				'execute_callback'    => array( EditFile::class, 'execute' ),
				'permission_callback' => array( Auth::class, 'check_ability_permission' ),
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'path'       => array( 'type' => 'string' ),
						'old_string' => array( 'type' => 'string' ),
						'new_string' => array( 'type' => 'string' ),
					),
					'required'   => array( 'path', 'old_string', 'new_string' ),
				),
				'meta'                => array(
					'mcp'         => $mcp_tool( 'edit' ),
					'annotations' => array( 'destructive' => true ),
				),
			),
			'layrshift/delete-file' => array(
				'label'               => __( 'Delete File', 'layrshift' ),
				'description'         => __( 'Delete a file or directory from the filesystem.', 'layrshift' ),
				'category'            => AbilityCategories::FILESYSTEM,
				'execute_callback'    => array( DeleteFile::class, 'execute' ),
				'permission_callback' => array( Auth::class, 'check_ability_permission' ),
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'path'      => array( 'type' => 'string' ),
						'recursive' => array( 'type' => 'boolean' ),
					),
					'required'   => array( 'path' ),
				),
				'meta'                => array(
					'mcp'         => $mcp_tool( 'delete' ),
					'annotations' => array( 'destructive' => true ),
				),
			),
			'layrshift/disable-file' => array(
				'label'               => __( 'Disable Sandbox File', 'layrshift' ),
				'description'         => __( 'Temporarily disable a sandbox PHP file without deleting it.', 'layrshift' ),
				'category'            => AbilityCategories::FILESYSTEM,
				'execute_callback'    => array( DisableFile::class, 'execute' ),
				'permission_callback' => array( Auth::class, 'check_ability_permission' ),
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'filename' => array( 'type' => 'string' ),
					),
					'required'   => array( 'filename' ),
				),
				'meta'                => array( 'mcp' => $mcp_tool( 'disable' ) ),
			),
			'layrshift/enable-file' => array(
				'label'               => __( 'Enable Sandbox File', 'layrshift' ),
				'description'         => __( 'Re-enable a previously disabled sandbox PHP file.', 'layrshift' ),
				'category'            => AbilityCategories::FILESYSTEM,
				'execute_callback'    => array( EnableFile::class, 'execute' ),
				'permission_callback' => array( Auth::class, 'check_ability_permission' ),
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'filename' => array( 'type' => 'string' ),
					),
					'required'   => array( 'filename' ),
				),
				'meta'                => array( 'mcp' => $mcp_tool( 'enable' ) ),
			),
			'layrshift/list-directory' => array(
				'label'               => __( 'List Directory', 'layrshift' ),
				'description'         => __( 'Browse the server filesystem.', 'layrshift' ),
				'category'            => AbilityCategories::FILESYSTEM,
				'execute_callback'    => array( ListDirectory::class, 'execute' ),
				'permission_callback' => array( Auth::class, 'check_ability_permission' ),
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'path'      => array( 'type' => 'string' ),
						'pattern'   => array( 'type' => 'string' ),
						'recursive' => array( 'type' => 'boolean' ),
						'max_depth' => array( 'type' => 'integer' ),
					),
					'required'   => array( 'path' ),
				),
				'meta'                => array(
					'mcp'         => $mcp_tool( 'list' ),
					'annotations' => array( 'readOnly' => true ),
				),
			),
			'layrshift/create-upload-link' => array(
				'label'               => __( 'Create Upload Link', 'layrshift' ),
				'description'         => __( 'Generate a temporary authenticated URL for file uploads.', 'layrshift' ),
				'category'            => AbilityCategories::FILESYSTEM,
				'execute_callback'    => array( CreateUploadLink::class, 'execute' ),
				'permission_callback' => array( Auth::class, 'check_ability_permission' ),
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'destination'     => array(
							'type' => 'string',
							'enum' => array( 'plugins', 'themes', 'uploads', 'custom' ),
						),
						'custom_path'     => array( 'type' => 'string' ),
						'expires_seconds' => array( 'type' => 'integer' ),
					),
					'required'   => array( 'destination' ),
				),
				'meta'                => array( 'mcp' => $mcp_tool( 'upload' ) ),
			),
		);

		if ( class_exists( 'LayrShift\\Abilities\\ExecutePhp' ) ) {
			$definitions['layrshift/execute-php'] = array(
				'label'               => __( 'Execute PHP', 'layrshift' ),
				'description'         => __( 'Execute PHP code with full access to the WordPress environment including $wpdb, all WordPress functions, and all active plugins.', 'layrshift' ),
				'category'            => AbilityCategories::CODE_EXECUTION,
				'execute_callback'    => array( 'LayrShift\\Abilities\\ExecutePhp', 'execute' ),
				'permission_callback' => array( Auth::class, 'check_ability_permission' ),
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'code' => array(
							'type'        => 'string',
							'description' => __( 'PHP code to execute. Do not include the opening <?php tag.', 'layrshift' ),
						),
					),
					'required'   => array( 'code' ),
				),
				'meta'                => array(
					'mcp'         => $mcp_tool( 'execute' ),
					'annotations' => array( 'destructive' => true ),
				),
			);
		}

		return $definitions;
	}
}
