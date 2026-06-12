---
name: divi
description: Build and edit Divi 5 pages, Divi Theme, Extra theme, Visual Builder modules, Theme Builder layouts, query loops, dynamic content, and global presets on a LayrShift site. Use for Divi theme, et_pb modules, Elegant Themes, Theme Builder header/footer, or "edit in Divi".
enable_prompt: true
enable_agentic: true
---

# Divi 5 and Divi Theme via LayrShift

Divi 5 stores **real builder modules** in `post_content` (block format). Compose section → row → column → module trees — never replace a Divi page with a raw HTML blob. This playbook targets **Divi 5+**; legacy Divi 4 shortcode pages need a different path.

## Start here

1. Call `layrshift/skill-get` with slug `divi` (this document) before improvising.
2. Probe environment and content format (blocks vs legacy shortcodes).
3. Duplicate the target to **draft** before structural edits unless the user explicitly approves live edits.
4. Re-read the module tree after every structural write.

## Probe environment

```php
return array(
    'et_builder'     => defined( 'ET_BUILDER_VERSION' ) ? ET_BUILDER_VERSION : null,
    'divi_theme'     => wp_get_theme()->get( 'Name' ),
    'stylesheet'     => get_stylesheet(),
    'template'       => get_template(),
    'is_divi_active' => function_exists( 'et_setup_theme' ) || defined( 'ET_BUILDER_VERSION' ),
);
```

If Divi / ET Builder is inactive, stop and ask the user to activate Divi or Extra on staging.

## Divi 5 vs legacy (version gate)

```php
$post_id = 123; // replace with target
$content = get_post_field( 'post_content', $post_id );
return array(
    'uses_blocks'         => has_blocks( $content ),
    'has_et_pb_shortcodes' => str_contains( $content, '[et_pb_' ),
    'post_status'         => get_post_status( $post_id ),
);
```

| Format | Action |
|--------|--------|
| **Blocks present** (`uses_blocks` true) | Primary Divi 5 path — edit module tree in `post_content` |
| **Shortcodes only** (`[et_pb_` without blocks) | Legacy Divi 4 — warn user; recommend Visual Builder migration or manual edit; do not fake with HTML |
| **Neither** | Page may not be Divi-built — probe Theme Builder assignment |

## Content model (Divi 5)

- **Hierarchy:** section → row → column → module (each node has a stable address in the tree).
- **Storage:** `post_content` holds block-based Divi modules; supplement with `_et_*` post meta.
- **Read tree:**

```php
$post_id = 123;
$content = get_post_field( 'post_content', $post_id );
$blocks  = parse_blocks( $content );
$et_meta = array_filter( get_post_meta( $post_id ), fn( $k ) => str_starts_with( (string) $k, '_et_' ), ARRAY_FILTER_USE_KEY );
return array(
    'block_count' => count( $blocks ),
    'blocks'      => $blocks,
    'et_meta_keys' => array_keys( $et_meta ),
);
```

- **Never** paste a full page as `core/html`, classic block, or front-end HTML — the Visual Builder must remain editable.

## Write workflow (pages)

1. `wp_insert_post` duplicate as `draft` or clone via `execute-php`.
2. Read full module tree from draft.
3. Apply changes as tree edits (add section/row/column/modules or edit module attributes).
4. Save with `wp_update_post` + correct `post_content` serialization (`serialize_blocks` if mixing WP blocks).
5. Clear Divi / object cache when available:

```php
if ( function_exists( 'et_core_clear_cache' ) ) {
    et_core_clear_cache();
}
if ( function_exists( 'et_fb_delete_builder_assets' ) ) {
    et_fb_delete_builder_assets( $post_id );
}
```

6. Re-read tree and compare structure to intent.

## Common tasks

### Build hero (heading + paragraph + CTA)

1. Duplicate target to draft.
2. Add top-level **section** → **row** → **column**.
3. Insert modules: heading (H1), text/blurb, button — set copy on desktop breakpoint.
4. Save, clear cache, re-read tree.
5. Ask user to open Visual Builder preview or use `layrshift/create-admin-access-link` for browser check.

### Edit a single module in place

1. Read full tree; locate module by block name / `attrs` / order index — record address before edit.
2. Change only that module's attributes or inner content.
3. Verify sibling addresses and parent chain unchanged unless restructuring was requested.

### Query loop on a grid/blog module

1. Identify a **loop-capable** module (blog grid, portfolio, etc.) in the tree.
2. Enable loop settings in module attributes (query: recent posts, category, count).
3. Bind per-item fields (title, excerpt, image) through module loop field map.
4. Re-read tree; preview loop output in Visual Builder.

### Dynamic content bindings

Bind module text/links to dynamic sources instead of hardcoded strings:

| Source | Typical use |
|--------|-------------|
| Post title | Page H1, breadcrumb |
| Site tagline | Header tagline |
| ACF field | `get_field()` via dynamic token if Divi exposes it; else document manual token name from Divi UI |
| WooCommerce | Product title, price on product templates |

Probe active dynamic plugins:

```php
return array(
    'acf'  => defined( 'ACF_VERSION' ),
    'woo'  => class_exists( 'WooCommerce' ),
);
```

Read existing modules first — copy dynamic token pattern from a working module on the same site.

### Global presets and design system

- Presets and global module styles live in Divi options / library posts (version-dependent).
- Probe library:

```php
$library = get_posts( array(
    'post_type'      => array( 'et_pb_layout', 'layout' ),
    'posts_per_page' => 20,
    'post_status'    => 'publish',
) );
return wp_list_pluck( $library, 'post_title', 'ID' );
```

- Apply preset to all buttons/headings on a page by setting module `preset` / global class attributes consistently — read one styled module as reference.

## Theme Builder (header, footer, body)

Site-wide templates are **separate posts**, not the page alone.

```php
// Discover ET-related post types (names vary by version)
return array_values( array_filter(
    get_post_types( array( 'public' => false ), 'names' ),
    fn( $t ) => str_contains( $t, 'et_' ) || str_contains( $t, 'layout' ) || str_contains( $t, 'tb_' )
) );
```

**Flow:**

1. Find Theme Builder assignment (which template applies to which scope).
2. Resolve **header layout post ID**, **body**, **footer** layout IDs.
3. Edit the **layout post** (e.g. sticky promo bar in header layout), not only the page post.
4. Re-read layout tree; verify assignment still points to edited layout.

For “edit site header” requests, always resolve header layout ID before editing modules.

## Child theme and custom code

- PHP/CSS overrides: **child theme** via `layrshift/read-file` and `layrshift/edit-file` on `wp-content/themes/{child}/`.
- Do not edit Divi parent theme files unless user insists — prefer child theme `functions.php`.
- Experimental hooks: `wp-content/layrshift-sandbox/` via `layrshift/write-file`.

## LayrShift abilities map

| Step | Ability |
|------|---------|
| Probe versions, read tree, save posts | `layrshift/execute-php` |
| Theme PHP/CSS | `layrshift/read-file`, `layrshift/edit-file` |
| Browse theme | `layrshift/list-directory` |
| Browser Visual Builder check | `layrshift/create-admin-access-link` |
| Non-Divi pages on same site | `layrshift/skill-get` → `gutenberg-edit-content` |

## Rules (non-negotiable)

- No HTML-only fake layouts — real Divi modules only.
- Draft-first; no `publish` without user confirmation.
- Re-read module tree after every structural change.
- Legacy `[et_pb_` shortcode pages: warn and do not claim Divi 5 parity.
- Staging only — same dev/staging scope as all LayrShift work.

## Verification checklist

- [ ] `uses_blocks` true (or user acknowledged legacy path)
- [ ] Module count and section/row/column nesting match plan
- [ ] Dynamic bindings and loop settings present in module attrs
- [ ] Theme Builder layouts edited by correct layout post ID
- [ ] Cache cleared; user invited to preview in Visual Builder

## Failure modes

| Symptom | Action |
|---------|--------|
| Tree corrupt / blank builder | Restore from revision; work on duplicate draft |
| Visual Builder shows broken layout | Re-read `post_content`; never use HTML blob workaround |
| Theme Builder change not visible | Edited wrong post — re-resolve layout assignment |
| PHP white screen after custom code | `?layrshift-safe-mode=1` or disable sandbox file |
