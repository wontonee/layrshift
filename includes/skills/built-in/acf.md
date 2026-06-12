---
name: acf
description: Manage Advanced Custom Fields field groups, CPTs, taxonomies, options pages, and field values on a LayrShift site. Use for ACF structure, custom fields, location rules, or options pages.
enable_prompt: true
enable_agentic: true
---

# ACF via LayrShift

Use ACF PHP APIs when ACF is active. Register fields in code for version-controlled setups; use `acf_add_local_field_group` in sandbox or mu-plugins for agent-authored structure.

## Probe

```php
return array(
    'acf' => defined( 'ACF_VERSION' ) ? ACF_VERSION : null,
    'acf_pro' => defined( 'ACF_PRO' ) && ACF_PRO,
);
```

## List field groups

```php
if ( ! function_exists( 'acf_get_field_groups' ) ) {
    return 'ACF not loaded';
}
$groups = acf_get_field_groups();
return array_map( fn( $g ) => array(
    'key'   => $g['key'],
    'title' => $g['title'],
    'location' => $g['location'] ?? array(),
), $groups );
```

## Read / write values

```php
// Read
get_field( 'hero_title', $post_id );

// Write
update_field( 'hero_title', 'Hello', $post_id );
```

## Register a field group in code

```php
acf_add_local_field_group( array(
    'key'    => 'group_layrshift_hero',
    'title'  => 'Hero',
    'fields' => array(
        array(
            'key'   => 'field_hero_title',
            'label' => 'Title',
            'name'  => 'hero_title',
            'type'  => 'text',
        ),
    ),
    'location' => array(
        array(
            array(
                'param'    => 'post_type',
                'operator' => '==',
                'value'    => 'page',
            ),
        ),
    ),
) );
```

Deploy registration PHP to sandbox first, verify, then move to a mu-plugin or child theme if the user approves.

## CPTs & taxonomies

- ACF can register CPTs/taxonomies (ACF 6+). Prefer `register_post_type` in sandbox/mu-plugin with unique slugs.
- Always flush rewrite rules only on staging after CPT changes.

## Verification

Re-list groups, read field values on a sample post, and confirm location rules match the intended post type.
