<?php
/**
 * Per-ability enable/disable policy for the Abilities Hub.
 *
 * @package LayrShift
 */

declare( strict_types=1 );

namespace LayrShift;

/**
 * Applies persisted ability rules after all providers register.
 */
final class AbilityPolicy {

	public const OPTION_KEY = 'layrshift_ability_rules';

	public const HUB_PAGE = 'layrshift-abilities';

	public static function init(): void {
		add_action( 'wp_abilities_api_init', array( self::class, 'apply' ), PHP_INT_MAX );
	}

	public static function is_valid_ability_name( string $ability_name ): bool {
		return 1 === preg_match( '/^[a-z0-9-]+\/[a-z0-9-\/]+$/', $ability_name );
	}

	public static function is_hub_protected( string $ability_name ): bool {
		return str_starts_with( $ability_name, 'mcp-adapter/' );
	}

	public static function is_hub_screen(): bool {
		if ( ! is_admin() ) {
			return false;
		}

		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( (string) $_GET['page'] ) ) : '';

		return self::HUB_PAGE === $page;
	}

	/**
	 * @return array<string, array{disabled: bool}>
	 */
	public static function get_rules(): array {
		$stored = get_option( self::OPTION_KEY, array() );
		if ( ! is_array( $stored ) ) {
			return array();
		}

		$rules = array();
		foreach ( $stored as $ability_name => $rule ) {
			if ( ! is_string( $ability_name ) || ! is_array( $rule ) || ! self::is_valid_ability_name( $ability_name ) ) {
				continue;
			}
			$rules[ $ability_name ] = array(
				'disabled' => in_array( $rule['disabled'] ?? false, array( true, '1', 1 ), true ),
			);
		}

		return $rules;
	}

	/**
	 * @param array<string, array{disabled?: bool}> $rules
	 */
	public static function update_rules( array $rules ): void {
		$clean = array();
		foreach ( $rules as $ability_name => $rule ) {
			if ( ! self::is_valid_ability_name( $ability_name ) ) {
				continue;
			}
			if ( empty( $rule['disabled'] ) ) {
				continue;
			}
			$clean[ $ability_name ] = array( 'disabled' => true );
		}

		update_option( self::OPTION_KEY, $clean, false );
	}

	public static function is_disabled( string $ability_name ): bool {
		$rules = self::get_rules();
		return ! empty( $rules[ $ability_name ]['disabled'] );
	}

	public static function apply(): void {
		if ( ! function_exists( 'wp_get_abilities' ) || ! function_exists( 'wp_unregister_ability' ) ) {
			return;
		}

		if ( self::is_hub_screen() ) {
			return;
		}

		$rules = self::get_rules();
		if ( array() === $rules ) {
			return;
		}

		foreach ( wp_get_abilities() as $ability ) {
			self::apply_rule( $ability, $rules );
		}
	}

	/**
	 * @param array<string, array{disabled: bool}> $rules
	 */
	private static function apply_rule( \WP_Ability $ability, array $rules ): void {
		$name = $ability->get_name();
		$rule = $rules[ $name ] ?? null;
		if ( null === $rule ) {
			return;
		}

		if ( $rule['disabled'] && ! self::is_hub_protected( $name ) ) {
			wp_unregister_ability( $name );
		}
	}
}
