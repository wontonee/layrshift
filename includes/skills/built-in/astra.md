---
name: astra
description: Configure Astra theme settings, header/footer builder layouts, and custom hooks on a LayrShift site. Use for Astra theme customization, colors, typography, or Astra Pro elements.
enable_prompt: true
enable_agentic: true
---

# Astra via LayrShift

Prefer **`layrshift/astra-*` abilities** when Astra is the active theme.

## Start here

1. `layrshift/skill-get` → `astra`
2. `layrshift/astra-get-status` — version and addon info
3. `layrshift/astra-get-settings` — colors, typography, layout defaults
4. `layrshift/astra-get-header-footer` — custom layout/hook posts

## Abilities

| Step | Ability |
|------|---------|
| Theme status | `layrshift/astra-get-status` |
| Theme settings | `layrshift/astra-get-settings` |
| Header/footer builder | `layrshift/astra-get-header-footer` |

## Probe

```php
return array(
    'astra' => defined( 'ASTRA_THEME_VERSION' ) ? ASTRA_THEME_VERSION : null,
    'astra_pro' => defined( 'ASTRA_EXT_VER' ) ? ASTRA_EXT_VER : null,
    'stylesheet' => get_stylesheet(),
);
```

## Settings

- Global options: `get_option( 'astra-settings' )`
- Customizer: `get_theme_mods()` for version-specific keys
- Apply changes on staging only; document each key for the user

## Compatibility

- Pair with `elementor` or `gutenberg-edit-content` skills for page content
- Header/footer custom layouts are often `astra-advanced-hook` CPT posts — list via `astra-get-header-footer` before editing

## Rules

- Child theme or Astra hooks for PHP; avoid editing parent `astra` theme files
- Confirm starter template imports with the user before running
