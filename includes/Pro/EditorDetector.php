<?php
/**
 * Detect active page builders for Template Studio.
 *
 * @package LayrShift
 */

declare( strict_types=1 );

namespace LayrShift\Pro;

final class EditorDetector {

	public static function is_elementor_available(): bool {
		return defined( 'ELEMENTOR_VERSION' ) && class_exists( '\Elementor\Plugin' );
	}

	public static function is_gutenberg_available(): bool {
		return function_exists( 'use_block_editor_for_post_type' );
	}

	/**
	 * Resolve editor slug from preference and site capabilities.
	 */
	public static function resolve( string $preference = 'auto' ): string {
		if ( 'gutenberg' === $preference && self::is_gutenberg_available() ) {
			return 'gutenberg';
		}

		if ( 'elementor' === $preference && self::is_elementor_available() ) {
			return 'elementor';
		}

		if ( 'auto' === $preference ) {
			$settings = ProSettings::get();
			$site_default = (string) ( $settings['default_editor'] ?? 'auto' );
			if ( in_array( $site_default, array( 'gutenberg', 'elementor' ), true ) ) {
				return self::resolve( $site_default );
			}

			return self::is_gutenberg_available() ? 'gutenberg' : 'elementor';
		}

		return self::is_gutenberg_available() ? 'gutenberg' : 'elementor';
	}

	/**
	 * @return array<int, array{slug: string, label: string, available: bool}>
	 */
	public static function list_editors(): array {
		return array(
			array(
				'slug'      => 'gutenberg',
				'label'     => __( 'Gutenberg (Block Editor)', 'layrshift' ),
				'available' => self::is_gutenberg_available(),
			),
			array(
				'slug'      => 'elementor',
				'label'     => __( 'Elementor', 'layrshift' ),
				'available' => self::is_elementor_available(),
			),
		);
	}
}
