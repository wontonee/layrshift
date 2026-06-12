---
name: generatepress
description: Configure GeneratePress theme settings, GP Premium elements, hooks, site library imports, and display conditions on a LayrShift site. Use for GeneratePress/GP Premium customization.
enable_prompt: true
enable_agentic: true
---

# GeneratePress via LayrShift

GeneratePress stores theme mods and GP Premium data in options/theme mods. Use `execute-php` and theme file reads.

## Probe

```php
$theme = wp_get_theme();
return array(
    'theme'      => $theme->get( 'Name' ),
    'stylesheet' => get_stylesheet(),
    'gp_premium' => defined( 'GP_PREMIUM_VERSION' ) ? GP_PREMIUM_VERSION : null,
);
```

## Theme mods & customizer

```php
return get_theme_mods();
```

Apply changes with `set_theme_mod( $key, $value )` on staging only. Document each mod for the user.

## GP Premium elements

- Elements are a custom post type (`gp_elements`). List with `get_posts( array( 'post_type' => 'gp_elements' ) )`.
- Hook elements use `generate_*` actions — verify hook names against GP Premium docs for the installed version.

## Child theme / hooks

- Prefer child theme `functions.php` via `layrshift/edit-file` on the child theme path.
- Use `generate_*` hooks; avoid editing parent theme files.

## Site library

- Import starter sites only when the user confirms — imports overwrite theme mods and may install plugins.

## Verification

Re-read theme mods and load front-end to confirm typography, colors, and layout settings.
