---
name: jetengine
description: Work with Crocoblock JetEngine meta boxes, custom content types, relations, options pages, and listings on a LayrShift site. Use for JetEngine CPTs, meta fields, glossaries, or dynamic listings.
enable_prompt: true
enable_agentic: true
---

# JetEngine via LayrShift

JetEngine stores configuration in custom tables and post types. Probe before editing.

## Probe

```php
return array(
    'jetengine' => defined( 'JET_ENGINE_VERSION' ) ? JET_ENGINE_VERSION : null,
);
```

## List meta boxes

```php
if ( ! function_exists( 'jet_engine' ) ) {
    return 'JetEngine not active';
}
$boxes = jet_engine()->meta_boxes->data->get_items();
return array_map( fn( $b ) => array( 'id' => $b->get_id(), 'name' => $b->get_arg( 'name' ) ), $boxes );
```

## Custom Content Types (CCT)

- CCT items are separate from `post` — use JetEngine's CCT APIs (`Jet_Engine\CPT\Custom_Content_Type_Factory`) rather than `wp_insert_post`.
- Read CCT schema before creating items.

## Relations

- Inspect relation definitions before linking posts/CCT items.
- Use JetEngine relation APIs; do not fake relations with loose post meta.

## Options pages

- JetEngine options pages store values in options with JetEngine-specific keys. Read existing keys before `update_option`.

## Verification

Re-list meta boxes/CCTs and read a sample item through JetEngine getters.
