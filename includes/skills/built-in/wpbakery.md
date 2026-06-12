---
name: wpbakery
description: Edit WPBakery Page Builder shortcode trees, rows, columns, grid items, and templates on a LayrShift site. Use for Visual Composer, WPBakery, vc_row, vc_column, or backend editor page structure.
enable_prompt: true
enable_agentic: true
---

# WPBakery via LayrShift

WPBakery stores layouts as **nested shortcodes** in `post_content` (`[vc_row]`, `[vc_column]`, `[vc_column_text]`, etc.). Broken nesting corrupts the entire page — parse before editing.

## Start here

1. Call `layrshift/skill-get` with slug `wpbakery` before improvising.
2. Probe WPBakery version and confirm `[vc_` shortcodes in target content.
3. Duplicate page to **draft** before shortcode surgery.
4. Re-parse shortcodes after every write to verify balanced tags.

## Probe environment

```php
return array(
    'wpbakery' => defined( 'WPB_VC_VERSION' ) ? WPB_VC_VERSION : null,
    'active'   => defined( 'WPB_VC_VERSION' ) || class_exists( 'Vc_Manager' ),
);
```

If WPBakery is inactive, stop and ask the user to activate on staging.

## Content model

```php
$post_id = 123;
$content = get_post_field( 'post_content', $post_id );
preg_match_all( '/\[vc_[^\]]+\]/', $content, $matches );
return array(
    'has_vc'        => str_contains( $content, '[vc_' ),
    'shortcode_count' => count( $matches[0] ?? array() ),
    'length'        => strlen( $content ),
    'post_status'   => get_post_status( $post_id ),
);
```

**Nesting rules:**

```
[vc_row]
  [vc_column width="1/1"]
    [vc_column_text]...[/vc_column_text]
  [/vc_column]
[/vc_row]
```

- Every `[vc_row]` must close with `[/vc_row]`.
- Columns must be **inside** rows, elements **inside** columns.
- Self-closing elements: `[vc_single_image image="123"]` — match site conventions.

## Read structure

```php
$content = get_post_field( 'post_content', $post_id );
$pattern = get_shortcode_regex( array( 'vc_row', 'vc_column', 'vc_column_text', 'vc_btn' ) );
preg_match_all( '/' . $pattern . '/s', $content, $matches, PREG_SET_ORDER );
return array_map( fn( $m ) => array(
    'tag'     => $m[2],
    'attrs'   => $m[3],
    'inner'   => substr( $m[5] ?? '', 0, 200 ),
), $matches );
```

Use `do_shortcode( $content )` in `execute-php` **only** for preview probes — writes must preserve shortcode format.

## Write workflow

1. Duplicate page to draft.
2. Copy existing shortcode block as template for attribute syntax on this site.
3. Apply surgical string edits or rebuild section with valid nesting.
4. `wp_update_post` with new `post_content`.
5. Re-parse with `preg_match_all` for `[vc_` — counts should match intent.

## Common tasks

### Build hero (heading + text + button)

1. Duplicate to draft.
2. Add row + full-width column.
3. Insert `[vc_custom_heading]`, `[vc_column_text]`, `[vc_btn]` (or site-equivalent shortcodes) inside column.
4. Verify three inner shortcodes inside one row.

### Edit single shortcode block

1. Locate block by unique inner text or attribute.
2. Change only that shortcode's attributes or inner content.
3. Do not disturb sibling shortcode boundaries.

### Grid / loop templates

Grid items may live in separate posts:

```php
return get_posts( array(
    'post_type'      => 'vc_grid_item',
    'posts_per_page' => 20,
) );
```

Edit `vc_grid_item` posts for loop card layout; page shortcode references grid by ID.

### WPBakery templates

```php
return get_posts( array(
    'post_type'      => array( 'vc_template', 'vc4_templates' ),
    'posts_per_page' => 20,
) );
```

Probe post type slug — varies by WPBakery version and add-ons.

## Backend vs front-end editor

- Prefer changes the user can validate in **WPBakery backend editor** (classic meta box).
- Complex attribute changes: ask user to open backend editor after draft save.

## LayrShift abilities map

| Step | Ability |
|------|---------|
| Parse/save shortcodes | `layrshift/execute-php` |
| Theme overrides | `layrshift/read-file`, `layrshift/edit-file` |
| Admin editor check | `layrshift/create-admin-access-link` |

## Rules

- Shortcode format only — not rendered HTML as the stored format.
- Draft-first; validate nesting after every edit.
- Copy attribute style from existing shortcodes on the site.
- Staging only.

## Verification checklist

- [ ] `[vc_row]` / `[/vc_row]` counts balanced
- [ ] Columns nested inside rows only
- [ ] `has_vc` true on updated draft
- [ ] User invited to WPBakery backend editor

## Failure modes

| Symptom | Action |
|---------|--------|
| Raw shortcodes on front | Broken tag — restore revision, re-check nesting |
| Backend editor empty | `post_content` not VC format — probe for page builder conflict |
| Grid wrong | Edited page not `vc_grid_item` template |
