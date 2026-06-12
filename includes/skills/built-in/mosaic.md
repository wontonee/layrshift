---
name: mosaic
description: Work with Mosaic theme templates, element trees, design variables, components, and utility classes on a LayrShift site. Use for Mosaic builder, Mosaic theme templates, utility classes, or design variables.
enable_prompt: true
enable_agentic: true
---

# Mosaic via LayrShift

Mosaic themes use **structured templates**, design variables, and utility classes. Edit template element trees and token references — not one-off inline CSS for site-wide changes.

## Start here

1. Call `layrshift/skill-get` with slug `mosaic` before improvising.
2. Confirm Mosaic is the active theme stack.
3. Read global variables before site-wide design changes.
4. Assign templates on **draft** pages for preview before go-live.

## Probe environment

```php
$theme = wp_get_theme();
return array(
    'theme'      => $theme->get( 'Name' ),
    'stylesheet' => get_stylesheet(),
    'template'   => get_template(),
    'mosaic'     => str_contains( strtolower( $theme->get( 'Name' ) ), 'mosaic' )
        || defined( 'MOSAIC_VERSION' ),
);
```

If Mosaic is not active, stop and confirm the user is on a Mosaic-based theme.

## Templates and element trees

```php
// List theme template sources
$theme_dir = get_stylesheet_directory();
return array(
    'theme_path' => $theme_dir,
    'has_templates_dir' => is_dir( $theme_dir . '/templates' ),
);
```

Use `layrshift/list-directory` on the active child theme for template files and Mosaic-specific directories.

Template posts (if used):

```php
$types = array_filter(
    get_post_types( array( 'public' => false ), 'names' ),
    fn( $t ) => stripos( $t, 'mosaic' ) !== false || stripos( $t, 'template' ) !== false
);
return array_values( $types );
```

Read assignment rules from theme docs or an existing assigned page before changing which template applies to archives or front page.

## Variables and utility classes

```php
// Probe theme mods and Mosaic options
return array(
    'theme_mods' => get_theme_mods(),
    'mosaic_opts' => get_option( 'mosaic_settings' ) ?: get_option( 'mosaic_options' ),
);
```

| Layer | Guidance |
|-------|----------|
| **Design variables** | Colors, spacing, typography tokens — change once for site-wide effect |
| **Utility classes** | Prefer utilities over custom CSS per element |
| **Components** | Reusable partials — read before duplicating markup |

Before renaming a utility class, search the theme template tree for references.

## Write workflow

1. Identify target: template file, template post, or page assignment.
2. Read current element tree / template markup.
3. Apply surgical edits preserving component and variable references.
4. Assign template to draft page for preview.
5. Re-read template; confirm variables still resolve.

## Common tasks

### Build hero section

1. Duplicate or create draft page with Mosaic template.
2. Add hero component or section block using existing site pattern.
3. Bind heading, text, CTA via variables where available.
4. Preview on draft URL.

### Site-wide spacing/color change

1. Read design variables in theme settings or options.
2. Update token values — not per-page inline styles.
3. Ask user to preview key templates (home, archive, single).

### Template assignment

1. Read Mosaic rules for front page vs blog index vs custom post types.
2. Apply assignment on staging draft only until user confirms.

## LayrShift abilities map

| Step | Ability |
|------|---------|
| Probe options | `layrshift/execute-php` |
| Theme files | `layrshift/list-directory`, `layrshift/read-file`, `layrshift/edit-file` |
| Preview | `layrshift/create-admin-access-link` |

## Rules

- Variables and utilities over one-off CSS.
- Draft preview before publishing template assignments.
- Child theme for overrides; staging only.

## Verification checklist

- [ ] Mosaic theme confirmed active
- [ ] Variables/tokens updated in correct layer
- [ ] Template assigned on draft page and previewed
- [ ] Utility class references intact

## Failure modes

| Symptom | Action |
|---------|--------|
| Styles not applied | Variable not defined — check theme mods |
| Wrong layout | Template assignment rules misread — compare to working page |
| Missing component | Path wrong — re-list theme components directory |
