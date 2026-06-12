---
name: bricks
description: Build and edit Bricks pages, templates, components, header/footer conditions, and dynamic data on a LayrShift site. Use for Bricks builder, Bricks theme, template conditions, global classes, or dynamic tags.
enable_prompt: true
enable_agentic: true
---

# Bricks via LayrShift

Bricks stores structured **element trees** in post meta (`_bricks_*` keys). Edit sections → containers → elements as JSON; preserve element `id` values Bricks references internally.

## Start here

1. Call `layrshift/skill-get` with slug `bricks` before improvising.
2. Probe Bricks version and discover meta keys on the target post.
3. Duplicate template/page to **draft** before structural edits.
4. Re-read element tree after every write.

## Probe environment

```php
return array(
    'bricks'  => defined( 'BRICKS_VERSION' ) ? BRICKS_VERSION : null,
    'active'  => class_exists( '\Bricks\Database' ) || defined( 'BRICKS_VERSION' ),
    'theme'   => wp_get_theme()->get( 'Name' ),
);
```

If Bricks is inactive, stop and ask the user to activate Bricks on staging.

## Content model

Bricks pages and templates store JSON element arrays in version-specific meta keys.

```php
$post_id = 123;
$all_meta = get_post_meta( $post_id );
$bricks_keys = array_filter( array_keys( $all_meta ), fn( $k ) => str_starts_with( $k, '_bricks' ) );
return array(
    'bricks_meta_keys' => array_values( $bricks_keys ),
    'bricks_meta'      => array_intersect_key( $all_meta, array_flip( $bricks_keys ) ),
);
```

**Preferred read via API:**

```php
if ( class_exists( '\Bricks\Database' ) ) {
    return \Bricks\Database::get_data( $post_id );
}
return array( 'error' => 'Bricks\Database not available' );
```

Element shape: each node has `id`, `name` (element type), `settings`, and optional `children`.

## Templates and theme parts

```php
$templates = get_posts( array(
    'post_type'      => 'bricks_template',
    'posts_per_page' => 50,
    'post_status'    => array( 'publish', 'draft' ),
) );
return array_map( fn( $p ) => array(
    'id'    => $p->ID,
    'title' => $p->post_title,
    'type'  => get_post_meta( $p->ID, '_bricks_template_type', true ),
), $templates );
```

| Template type | Use |
|---------------|-----|
| `header` | Site header — edit this post for nav/promo bar |
| `footer` | Site footer |
| `archive`, `search`, `error` | Archive and utility templates |
| `content` / `section` | Reusable sections |

**Conditions:** template assignment uses Bricks conditions (URL, post type, etc.). Read existing condition JSON from template meta before changing assignments.

## Write workflow

1. Duplicate or draft the target template/page.
2. Read full element tree via `\Bricks\Database::get_data()` or meta.
3. Apply surgical tree edits — **never regenerate random `id` values**.
4. Save via Bricks API when available:

```php
if ( class_exists( '\Bricks\Database' ) && isset( $new_tree ) ) {
    \Bricks\Database::set_data( $post_id, $new_tree );
}
```

5. If API save unavailable, update the correct `_bricks_*` meta key on draft only; ask user to validate in builder.
6. Re-read tree and compare element count and hierarchy.

## Common tasks

### Build hero (heading + text + button)

1. Duplicate page to draft.
2. Add section → container → elements: `heading`, `text-basic`, `button`.
3. Set typography via Bricks settings or global classes.
4. Save, re-read tree, confirm three sibling elements.

### Edit single element in place

1. Find element by `id` in tree (recursive walk).
2. Update only `settings` for that `id`; keep `name` and `id` unchanged.
3. Re-read; verify parent `children` order intact.

### Query loop (Posts element)

1. Add or locate `posts` / `post-taxonomy` element.
2. Configure query in `settings`: `post_type`, `posts_per_page`, filters.
3. Nest loop item template elements as children per Bricks loop pattern on this site (read a working loop first).

### Dynamic data tags

Use Bricks dynamic data in element `settings` (`{post_title}`, `{site_tagline}`, ACF field tags):

```php
return array(
    'acf' => defined( 'ACF_VERSION' ),
    'woo' => class_exists( 'WooCommerce' ),
);
```

Copy dynamic tag syntax from an existing element on the same site — format varies by Bricks version.

### Global classes, variables, components

- **Global classes:** site-wide utility classes in Bricks settings — read before renaming classes in use.
- **Variables:** design tokens (color, spacing) — change in Bricks panel for site-wide effect.
- **Components:** reusable element groups — list component posts or global elements before duplicating markup.

```php
// Components may be stored as bricks_template with type component — probe:
$components = get_posts( array(
    'post_type'      => 'bricks_template',
    'posts_per_page' => 30,
    'meta_query'     => array(
        array( 'key' => '_bricks_template_type', 'value' => 'section' ),
    ),
) );
return wp_list_pluck( $components, 'post_title', 'ID' );
```

## Child theme and custom elements

- Custom element PHP: child theme via `layrshift/read-file` / `layrshift/edit-file`.
- Do not edit Bricks core plugin files.
- Sandbox experiments: `wp-content/layrshift-sandbox/`.

## LayrShift abilities map

| Step | Ability |
|------|---------|
| Probe, read/save trees | `layrshift/execute-php` |
| Theme PHP | `layrshift/read-file`, `layrshift/edit-file` |
| Builder preview | `layrshift/create-admin-access-link` |

## Rules

- Preserve element `id` values across edits.
- Draft-first; no publish without user confirmation.
- Header/footer tasks → edit `bricks_template` post, not arbitrary pages.
- Staging only.

## Verification checklist

- [ ] Correct `_bricks_*` meta key updated
- [ ] Element `id` values unchanged unless intentionally adding nodes
- [ ] Template conditions still match intended URLs/post types
- [ ] Dynamic tags present in settings
- [ ] User invited to Bricks builder preview

## Failure modes

| Symptom | Action |
|---------|--------|
| Builder shows empty canvas | Wrong meta key or corrupt JSON — restore revision |
| Styles wrong | Global class renamed — search tree for class references |
| Header change invisible | Edited page instead of header template |
| Loop empty | Query settings wrong — compare to working posts element |
