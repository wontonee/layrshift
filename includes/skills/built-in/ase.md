---
name: ase
description: Manage ASE (Advanced Schema Editor / custom fields plugin) field groups, post types, taxonomies, and values on a LayrShift site. Use when ASE registers the site's content model.
enable_prompt: true
enable_agentic: true
---

# ASE via LayrShift

ASE (as integrated by Novamira Pro) registers custom field groups, post types, and taxonomies. Probe the exact ASE plugin slug on the site — naming varies by distribution.

## Probe

```php
$plugins = get_option( 'active_plugins', array() );
$ase_like = array_filter( $plugins, fn( $p ) => stripos( $p, 'ase' ) !== false || stripos( $p, 'schema' ) !== false );
return array(
    'candidates' => array_values( $ase_like ),
    'post_types' => array_keys( get_post_types( array( '_builtin' => false ) ) ),
);
```

## Discover field groups

List custom post types and meta registered on the site. Read ASE admin export or options table entries before writing structure.

```php
global $wpdb;
// Probe options — adjust prefix after identifying ASE option keys
$rows = $wpdb->get_results( "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE '%ase%' LIMIT 20" );
return wp_list_pluck( $rows, 'option_name' );
```

## Workflow

1. Identify ASE version and storage from plugin headers (`layrshift/read-file` on main plugin file).
2. Read existing groups/CPTs before creating duplicates.
3. Write values through ASE APIs when documented; otherwise use `update_post_meta` with confirmed field keys.

## Verification

Create a draft post of the target type, set fields, re-read values in admin and via PHP.
