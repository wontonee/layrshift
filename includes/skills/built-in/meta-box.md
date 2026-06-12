---
name: meta-box
description: Manage Meta Box field groups, custom post types, taxonomies, relationships, and meta values on a LayrShift site. Use for Meta Box (meta-box.io) structure and data tasks.
enable_prompt: true
enable_agentic: true
---

# Meta Box via LayrShift

Meta Box registers fields via filters and storage APIs. Prefer `rwmb_*` and Meta Box registration arrays in code.

## Probe

```php
return array(
    'meta_box' => defined( 'RWMB_VER' ) ? RWMB_VER : null,
);
```

## List field groups

Field groups are often registered in code or stored as `meta-box` posts:

```php
$groups = get_posts( array(
    'post_type'      => 'meta-box',
    'posts_per_page' => -1,
    'post_status'    => 'publish',
) );
return wp_list_pluck( $groups, 'post_title', 'ID' );
```

## Register fields in code

```php
add_filter( 'rwmb_meta_boxes', function ( $meta_boxes ) {
    $meta_boxes[] = array(
        'title'  => 'Hero',
        'id'     => 'hero-fields',
        'fields' => array(
            array( 'name' => 'Title', 'id' => 'hero_title', 'type' => 'text' ),
        ),
        'post_types' => array( 'page' ),
    );
    return $meta_boxes;
} );
```

Deploy via sandbox/mu-plugin; test on a draft page.

## Read / write values

```php
rwmb_meta( 'hero_title', '', $post_id );
update_post_meta( $post_id, 'hero_title', 'Value' ); // key matches field id
```

## Relationships

Use Meta Box relationship APIs (`MB_Relationships_API`) — inspect registered relationships before linking posts.

## Verification

Confirm fields appear in admin for the target post type and values persist after save.
