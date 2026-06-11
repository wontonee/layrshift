<?php
/**
 * create_upload_link ability.
 *
 * @package LayrShift
 */

declare( strict_types=1 );

namespace LayrShift\Abilities;

use LayrShift\UploadToken;

final class CreateUploadLink {

	use AbilityTrait;

	/**
	 * @param array<string, mixed> $input
	 * @return array<string, mixed>|\WP_Error
	 */
	public static function execute( array $input ) {
		return self::run_logged(
			'layrshift/create-upload-link',
			$input,
			static function () use ( $input ) {
				$destination = (string) ( $input['destination'] ?? 'uploads' );
				$custom      = isset( $input['custom_path'] ) ? (string) $input['custom_path'] : null;
				$expires     = (int) ( $input['expires_seconds'] ?? 300 );

				$result = UploadToken::create( $destination, $custom, $expires );
				if ( is_wp_error( $result ) ) {
					return $result;
				}

				unset( $result['token'] );

				return $result;
			}
		);
	}
}
