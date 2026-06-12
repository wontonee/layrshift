---
name: breakdance
description: Build Breakdance templates, element trees, design tokens, global settings, display conditions, and popups on a LayrShift site. Use for Breakdance builder pages, Breakdance theme templates, popups, or design sets.
enable_prompt: true
enable_agentic: true
---

# Breakdance via LayrShift

Breakdance stores **JSON element trees** and design-set configuration. Edit elements and properties in structured JSON — never replace a Breakdance page with raw HTML.

## Start here

1. Call `layrshift/skill-get` with slug `breakdance` before improvising.
2. Probe version and discover meta keys (names vary by Breakdance version).
3. Duplicate template to **draft** before structural edits.
4. Re-read element tree after every write; preserve element IDs.

## Probe environment

```php
return array(
    'breakdance' => defined( 'BREAKDANCE_VERSION' ) ? BREAKDANCE_VERSION : null,
    'active'     => defined( 'BREAKDANCE_VERSION' ) || class_exists( '\Breakdance\Plugin' ),
);
```

If Breakdance is inactive, stop and ask the user to activate on staging.

## Content model

Breakdance pages and templates store builder data in post meta (key names often contain `breakdance`).

```php
$post_id = 123;
$meta = get_post_meta( $post_id );
$bd = array_filter( $meta, fn( $k ) => stripos( $k, 'breakdance' ) !== false, ARRAY_FILTER_USE_KEY );
return array(
    'meta_keys' => array_keys( $bd ),
    'meta'      => $bd,
);
```

Element trees are nested JSON with stable `id` fields. Read a working page on the site before inventing structure.

## Template types

```php
// Breakdance uses custom post types for templates — probe installed types
return array_values( array_filter(
    get_post_types( array( 'public' => false ), 'names' ),
    fn( $t ) => stripos( $t, 'breakdance' ) !== false
) );
```

| Document | Typical use |
|----------|-------------|
| Page template | Single page layout |
| Header / footer | Site-wide parts |
| Popup | Modal overlays with triggers |
| Global block | Reusable sections |

For header/footer requests, resolve the **template post** assigned to that slot before editing modules.

## Write workflow

1. Duplicate target to draft.
2. Read full JSON tree from meta.
3. Apply surgical edits — preserve element `id` and design token references.
4. Save meta on draft; clear Breakdance cache if a helper exists.
5. Re-read tree; compare element count and nesting.

## Design tokens and global settings

Site-wide colors, typography, and spacing live in Breakdance **global settings** and **design sets**, not only page meta.

```php
// Probe options — key names version-dependent
$opts = array();
foreach ( array( 'breakdance_settings', 'breakdance_global_settings' ) as $key ) {
    $val = get_option( $key );
    if ( $val ) {
        $opts[ $key ] = $val;
    }
}
return $opts ?: array( 'note' => 'Probe wp_options for breakdance_* keys' );
```

Before changing palette site-wide, read active design set and document which tokens elements reference.

## Common tasks

### Build hero (heading + text + button)

1. Duplicate page to draft.
2. Add section → add heading, text, button elements via tree structure matching an existing Breakdance page.
3. Bind typography to design tokens where available.
4. Save, re-read, confirm three content elements.

### Edit single element

1. Locate element by `id` in JSON tree.
2. Update only that element's `properties` / `settings`.
3. Verify siblings and parent `children` unchanged.

### Query loop (Post loop element)

1. Add or find post-list / loop element type used on this site.
2. Configure query: post type, count, order, taxonomy in element properties.
3. Preview loop output in Breakdance builder.

### Dynamic data

Bind headings, links, and images through Breakdance dynamic field pickers in element properties. Copy token syntax from a working element — do not hardcode URLs in PHP.

## Popups and display conditions

Popups are **separate Breakdance documents** with display rules (URL, user role, click trigger, etc.).

```php
$popups = get_posts( array(
    'post_type'      => 'breakdance_popup', // probe if slug differs
    'posts_per_page' => 30,
    'post_status'    => 'publish',
) );
if ( empty( $popups ) ) {
    // Fallback: search all post types containing 'popup'
    $types = get_post_types( array(), 'names' );
    $popup_types = array_filter( $types, fn( $t ) => stripos( $t, 'popup' ) !== false );
    return array( 'popup_post_types' => array_values( $popup_types ) );
}
return wp_list_pluck( $popups, 'post_title', 'ID' );
```

**Flow:** list popups → read trigger/condition JSON → edit popup tree → verify conditions still target intended pages.

## Child theme and custom code

- PHP/CSS: child theme via `layrshift/read-file` / `layrshift/edit-file`.
- Sandbox: `wp-content/layrshift-sandbox/`.

## LayrShift abilities map

| Step | Ability |
|------|---------|
| Probe, read/save trees | `layrshift/execute-php` |
| Theme files | `layrshift/read-file`, `layrshift/edit-file` |
| Builder preview | `layrshift/create-admin-access-link` |

## Rules

- JSON tree edits only — no HTML blob workarounds.
- Draft-first; preserve element IDs.
- Popup/condition edits require reading both tree and trigger rules.
- Staging only.

## Verification checklist

- [ ] Correct meta key updated for installed version
- [ ] Design token references intact
- [ ] Popup conditions still match intended triggers
- [ ] User invited to Breakdance preview

## Failure modes

| Symptom | Action |
|---------|--------|
| Blank builder | Corrupt JSON — restore revision |
| Styles wrong | Token renamed — search tree for token refs |
| Popup not showing | Conditions wrong — re-read trigger meta |
| Wrong header | Edited page not header template |
