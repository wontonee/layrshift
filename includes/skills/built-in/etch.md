---
name: etch
description: Build Etch pages with etch blocks, components, stylesheets, loops, and dynamic data on a LayrShift site. Use for Etch builder, Etch components, design tokens, or component-based layouts.
enable_prompt: true
enable_agentic: true
---

# Etch via LayrShift

Etch uses **block-based components** and centralized stylesheets. Compose pages from Etch blocks and reusable components — not ad-hoc HTML dumps.

## Start here

1. Call `layrshift/skill-get` with slug `etch` before improvising.
2. Probe Etch version and read storage format on target post.
3. Duplicate to **draft** before structural edits.
4. Re-read block/component structure after every write.

## Probe environment

```php
return array(
    'etch'   => defined( 'ETCH_VERSION' ) ? ETCH_VERSION : ( class_exists( 'Etch\Plugin' ) ? 'active' : null ),
    'theme'  => wp_get_theme()->get( 'Name' ),
);
```

If Etch is inactive, stop and ask the user to activate on staging.

## Content model

```php
$post_id = 123;
$content = get_post_field( 'post_content', $post_id );
$meta    = get_post_meta( $post_id );
$etch_meta = array_filter( $meta, fn( $k ) => stripos( $k, 'etch' ) !== false, ARRAY_FILTER_USE_KEY );
return array(
    'uses_blocks' => has_blocks( $content ),
    'blocks'      => has_blocks( $content ) ? parse_blocks( $content ) : null,
    'etch_meta_keys' => array_keys( $etch_meta ),
);
```

Etch may store layout in blocks, post meta, or both — always probe the target post before writing.

## Components library

Before duplicating markup, list reusable components:

```php
// Probe component post types — names version-dependent
$types = array_filter(
    get_post_types( array( 'public' => false ), 'names' ),
    fn( $t ) => stripos( $t, 'etch' ) !== false || stripos( $t, 'component' ) !== false
);
return array_values( $types );
```

Prefer inserting an existing **component** over copying raw block markup.

## Write workflow

1. Duplicate page to draft.
2. Read blocks + Etch meta.
3. Add or edit Etch blocks/components following patterns from a working page on the site.
4. Save via `wp_update_post` and/or meta update.
5. Re-read structure; confirm components resolve.

## Stylesheets and design tokens

- Global styles live in Etch stylesheets — read before site-wide color/spacing changes.
- Prefer design tokens and component classes over inline `style` attributes in blocks.

```php
// Probe Etch options
foreach ( array( 'etch_settings', 'etch_stylesheets' ) as $key ) {
    $val = get_option( $key );
    if ( $val ) {
        return array( $key => $val );
    }
}
return array( 'note' => 'Search wp_options for etch_* keys' );
```

## Loops and dynamic data

1. Find a working Etch loop on the site; copy its block attributes.
2. Bind fields (title, excerpt, image) through Etch's data layer in block settings.
3. Probe dynamic plugins:

```php
return array(
    'acf' => defined( 'ACF_VERSION' ),
    'woo' => class_exists( 'WooCommerce' ),
);
```

## LayrShift abilities map

| Step | Ability |
|------|---------|
| Probe, read/save | `layrshift/execute-php` |
| Theme PHP/CSS | `layrshift/read-file`, `layrshift/edit-file` |
| Preview | `layrshift/create-admin-access-link` |

## Rules

- Component-first; no one-off HTML page replacements.
- Draft-first; staging only.
- Re-read blocks after structural edits.

## Verification checklist

- [ ] Components referenced correctly
- [ ] Stylesheet tokens applied (not inline-only styling)
- [ ] User invited to Etch editor preview

## Failure modes

| Symptom | Action |
|---------|--------|
| Unstyled blocks | Stylesheet not linked — check Etch global styles |
| Component missing | Wrong component ID — re-list library |
| Loop empty | Copy loop config from working page |
