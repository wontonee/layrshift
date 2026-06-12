---
name: elementor
description: Build and edit Elementor pages, templates, global styles, kits, dynamic tags, and atomic widgets on a LayrShift site. Use for Elementor page builder, Elementor Pro, Site Settings, kit, v3/v4 widgets, or "edit this Elementor page".
enable_prompt: true
enable_agentic: true
---

# Elementor via LayrShift

Elementor stores structured **element trees** in post meta (`_elementor_data`), not rendered HTML. Edit sections → columns → widgets as JSON; never replace an Elementor page with a raw HTML block.

## Start here

1. Call `layrshift/skill-get` with slug `elementor` before improvising.
2. Use **`layrshift/elementor-*` abilities** for document read/write (not raw `execute-php` JSON surgery).
3. Duplicate to **draft** before structural edits unless user approves live edit.
4. Re-read with `layrshift/elementor-get-document` after every save.

## Probe environment

```php
return array(
    'elementor'    => defined( 'ELEMENTOR_VERSION' ) ? ELEMENTOR_VERSION : null,
    'pro'          => defined( 'ELEMENTOR_PRO_VERSION' ) ? ELEMENTOR_PRO_VERSION : null,
    'active'       => did_action( 'elementor/loaded' ) || class_exists( '\Elementor\Plugin' ),
    'active_kit'   => get_option( 'elementor_active_kit' ),
);
```

If Elementor is inactive, stop and ask the user to install/activate on staging.

## Document types and storage

| Type | Typical `post_type` | Key meta |
|------|---------------------|----------|
| Page / post | `page`, `post` | `_elementor_data`, `_elementor_edit_mode` |
| Template | `elementor_library` | Same + template type meta |
| Kit | `elementor_library` (kit) | Global colors, typography, settings |

Probe a target post:

```php
$post_id = 123;
return array(
    'post_type'   => get_post_type( $post_id ),
    'edit_mode'   => get_post_meta( $post_id, '_elementor_edit_mode', true ),
    'template_type' => get_post_meta( $post_id, '_elementor_template_type', true ),
    'raw_data'    => get_post_meta( $post_id, '_elementor_data', true ),
    'elements'    => json_decode( get_post_meta( $post_id, '_elementor_data', true ), true ),
);
```

## Read page structure

**Preferred:** `layrshift/elementor-get-document` with `post_id`. Returns shaped `elements`, `json_valid`, `template_type`, and `page_settings`.

**Fallback** (`execute-php` for probes only):

```php
$post_id = 123;
$raw = get_post_meta( $post_id, '_elementor_data', true );
return array(
    'edit_mode' => get_post_meta( $post_id, '_elementor_edit_mode', true ),
    'elements'  => json_decode( $raw, true ),
    'json_valid' => json_last_error() === JSON_ERROR_NONE,
);
```

## v3 vs v4 / atomic widgets

Probe element types in existing documents before adding widgets:

```php
function elementor_collect_types( $elements, &$types = array() ) {
    foreach ( (array) $elements as $el ) {
        if ( ! empty( $el['elType'] ) ) {
            $types[] = ( $el['widgetType'] ?? $el['elType'] );
        }
        if ( ! empty( $el['elements'] ) ) {
            elementor_collect_types( $el['elements'], $types );
        }
    }
    return array_values( array_unique( $types ) );
}
$data = json_decode( get_post_meta( $post_id, '_elementor_data', true ), true );
return elementor_collect_types( $data );
```

| Path | Guidance |
|------|----------|
| **Legacy v3 widgets** | `widgetType` like `heading`, `button`, `image` |
| **v4 / atomic** | Different element types — copy structure from an existing atomic widget on the site |
| **Migration** | Duplicate to draft; never in-place migrate live pages without user sign-off |

## Write workflow

1. `layrshift/elementor-get-document` on the target (draft duplicate).
2. Plan edits as element tree changes — preserve every element `id`.
3. `layrshift/elementor-save-document` with `post_id` and `elements` (optional `page_settings`).
4. Published posts require `allow_publish: true` and explicit user approval.
5. Re-read with `elementor-get-document`; confirm `element_count` and structure.

## Common tasks

### Build hero (heading + text + button)

1. Duplicate page to draft.
2. Add section → inner section or column → widgets: `heading`, `text-editor`, `button`.
3. Set typography/spacing in widget settings, not inline HTML.
4. Save, re-read tree, confirm three widgets under one section.

### Edit single widget in place

1. Walk element tree by `id`; record target widget `id` before edit.
2. Change only `settings` for that widget; preserve `id`, `elType`, `widgetType`.
3. Re-read and confirm widget count unchanged.

### Query loop (Posts / Loop Grid / Archive templates)

1. Identify loop-capable widget (`posts`, `loop-grid`, etc.) or Theme Builder archive template.
2. Configure query: `post_type`, `posts_per_page`, taxonomy filters in widget `settings`.
3. For Pro loop templates, edit `elementor_library` template with correct `_elementor_template_type`.

### Dynamic tags

Bind titles, URLs, and images through Elementor dynamic tag structure in widget `settings`:

```php
// Probe: list registered dynamic tags when unsure
if ( class_exists( '\Elementor\Plugin' ) ) {
    $tags = \Elementor\Plugin::$instance->dynamic_tags->get_tags_config();
    return array_keys( $tags );
}
```

Never hardcode permalinks in PHP when a dynamic tag exists for that field.

## Global styles and kits

```php
$kit_id = get_option( 'elementor_active_kit' );
return array(
    'kit_id'   => $kit_id,
    'kit_meta' => $kit_id ? get_post_meta( $kit_id ) : null,
);
```

- Site-wide palette and typography: edit **active kit** post, not individual pages.
- After kit changes, ask user to verify **Site Settings** in Elementor UI.

## Theme Builder (header, footer, single)

**Preferred:** `layrshift/elementor-list-templates` — optional `template_type` filter (`header`, `footer`, `single`, etc.).

For header/footer edits, `elementor-get-document` on the **template post ID** returned by list-templates — not the page post.

## Files and custom widgets

- Theme widget PHP: `layrshift/list-directory` + `layrshift/read-file` on child theme.
- Elementor uploads: `wp-content/uploads/elementor/`.
- Experimental PHP: `wp-content/layrshift-sandbox/` only.

## LayrShift abilities map

| Step | Ability |
|------|---------|
| Read document tree | `layrshift/elementor-get-document` |
| Save document tree | `layrshift/elementor-save-document` |
| List Theme Builder templates | `layrshift/elementor-list-templates` |
| Version/kit probes | `layrshift/execute-php` |
| Custom widget PHP | `layrshift/read-file`, `layrshift/edit-file` |
| Preview in editor | `layrshift/create-admin-access-link` |

## Rules

- Tree edits only — no HTML blob replacements.
- Draft-first; preserve element `id` values.
- Re-read `_elementor_data` after every structural change.
- Staging scope only.

## Verification checklist

- [ ] JSON decodes without error
- [ ] Section/column/widget counts match plan
- [ ] Dynamic tags present in widget settings where needed
- [ ] Theme Builder tasks edited correct `elementor_library` post
- [ ] User invited to Elementor preview

## Failure modes

| Symptom | Action |
|---------|--------|
| Blank Elementor canvas | Invalid JSON — restore revision; fix `json_decode` errors |
| Styles missing | Regenerate CSS in Elementor or re-save document |
| Wrong header changed | Edited page instead of header template — re-list templates |
| Widget type unknown | Copy element structure from existing site page |
