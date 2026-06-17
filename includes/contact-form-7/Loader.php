<?php

declare(strict_types=1);

namespace LayrShift\ContactForm7;

use LayrShift\Plugin;

final class Loader {

	public static function register_abilities(): void {
		if ( ! Plugin::meets_requirements() || ! Plugin::is_abilities_enabled() ) {
			return;
		}

		require_once __DIR__ . '/bootstrap.php';

		if ( ! is_contact_form_7_available() ) {
			return;
		}

		foreach ( array( 'get-status.php', 'list-forms.php', 'get-form.php' ) as $file ) {
			require_once __DIR__ . '/' . $file;
		}
	}

	/** @return list<string> */
	public static function ability_names(): array {
		require_once __DIR__ . '/bootstrap.php';

		if ( ! is_contact_form_7_available() ) {
			return array();
		}

		return array(
			'layrshift/contact-form-7-get-status',
			'layrshift/contact-form-7-list-forms',
			'layrshift/contact-form-7-get-form',
		);
	}
}
