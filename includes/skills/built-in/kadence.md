---
name: kadence
description: Configure Kadence theme settings, Kadence Blocks, header builder, forms, and color palettes on a LayrShift site. Use for Kadence theme or blocks customization.
enable_prompt: true
enable_agentic: true
---

# Kadence via LayrShift

Kadence combines theme options, block patterns, and header/footer builder posts.

## Probe

```php
return array(
    'kadence' => defined( 'KADENCE_VERSION' ) ? KADENCE_VERSION : null,
    'kadence_blocks' => defined( 'KADENCE_BLOCKS_VERSION' ) ? KADENCE_BLOCKS_VERSION : null,
);
```

## Theme settings

```php
return get_option( 'theme_mods_kadence' ) ?: get_theme_mods();
```

Apply typography/color/spacing via theme mods or Kadence customizer options — staging only.

## Kadence Blocks

- Prefer registered `kadence/*` blocks via `gutenberg-edit-content` for page builds.
- Discover blocks: `WP_Block_Type_Registry::get_instance()->get_all_registered()` filtered for `kadence/`.

## Header / footer builder

- Custom headers are typically `kadence_header` or related CPTs (version-dependent). List posts and read meta before editing.

## Forms

- Kadence Forms entries are plugin-specific — probe form post type and storage before creating forms programmatically.

## Verification

Confirm theme mods, block attributes on a test page, and header assignment on front-end.
