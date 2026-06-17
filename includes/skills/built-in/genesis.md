---
name: genesis
description: Configure Genesis Framework theme settings, layouts, hooks, and per-post Genesis meta on a LayrShift site. Use for Genesis child themes, StudioPress hooks, layouts, or breadcrumbs.
enable_prompt: true
enable_agentic: true
---

# Genesis Framework via LayrShift

Prefer **`layrshift/genesis-*` abilities** when Genesis is the active parent theme. Edit child themes only — never modify parent Genesis files.

## Start here

1. `layrshift/skill-get` → `genesis`
2. `layrshift/genesis-get-status` — parent/child theme and layouts
3. `layrshift/genesis-get-settings` — global Genesis options
4. `layrshift/genesis-get-post-meta` — per-post layout and visibility

## Abilities

| Step | Ability |
|------|---------|
| Theme status | `layrshift/genesis-get-status` |
| Global settings | `layrshift/genesis-get-settings` |
| Post Genesis meta | `layrshift/genesis-get-post-meta` |

## Probe

```php
return array(
    'genesis' => function_exists( 'genesis' ),
    'template' => get_template(),
    'stylesheet' => get_stylesheet(),
    'version' => defined( 'PARENT_THEME_VERSION' ) ? PARENT_THEME_VERSION : null,
);
```

## REST API

Genesis exposes REST endpoints when active:

- `GET /wp-json/genesis/v1/layouts/site`
- `GET /wp-json/genesis/v1/breadcrumbs`
- `GET /wp-json/genesis/v1/reading-settings`

## Hooks (child theme `functions.php`)

Common action hooks:

- `genesis_before_header`, `genesis_header`, `genesis_after_header`
- `genesis_before_entry`, `genesis_entry_header`, `genesis_entry_content`, `genesis_entry_footer`
- `genesis_before_footer`, `genesis_footer`, `genesis_after_footer`

Use `add_action( 'hook_name', 'callback', priority )` in the child theme via `layrshift/edit-file`.

## Rules

- Child theme only for PHP customizations
- Prefer Genesis meta and `genesis_get_option()` over hardcoded layout HTML
- Pair with `yoast` or `rank-math` skill for SEO when those plugins are active instead of legacy `_genesis_*` SEO fields
