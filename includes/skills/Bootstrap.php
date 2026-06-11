<?php
/**
 * Skills module bootstrap.
 *
 * @package LayrShift
 */

declare( strict_types=1 );

namespace LayrShift\Skills;

use LayrShift\Skills\Abilities\SkillDelete;
use LayrShift\Skills\Abilities\SkillEdit;
use LayrShift\Skills\Abilities\SkillGet;
use LayrShift\Skills\Abilities\SkillWrite;
use LayrShift\Skills\Prompts;

/**
 * Wires the LayrShift skills subsystem.
 */
final class Bootstrap {

	public static function init(): void {
		add_action( 'init', array( Cpt::class, 'register' ) );

		add_filter( 'layrshift_skill_lookup_sources', array( BuiltIn::class, 'register_source' ) );
		add_filter( 'layrshift_discover_abilities_instructions', array( Catalog::class, 'inject' ), 10 );

		add_action( 'wp_abilities_api_init', array( SkillGet::class, 'register_category' ), 5 );
		add_action( 'wp_abilities_api_init', array( Prompts::class, 'register_dynamic_abilities' ), 500 );
		add_action( 'wp_abilities_api_init', array( SkillGet::class, 'register' ), 20 );
		add_action( 'wp_abilities_api_init', array( SkillWrite::class, 'register' ), 20 );
		add_action( 'wp_abilities_api_init', array( SkillEdit::class, 'register' ), 20 );
		add_action( 'wp_abilities_api_init', array( SkillDelete::class, 'register' ), 20 );
	}
}
