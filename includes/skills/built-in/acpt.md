---
name: acpt
description: Manage ACPT (Advanced Custom Post Types) post types, taxonomies, meta groups, and option pages on a LayrShift site. Use for ACPT content model tasks.
enable_prompt: true
enable_agentic: true
---

# ACPT via LayrShift

ACPT registers CPTs, taxonomies, and meta through its admin UI and stored config. Probe before programmatic changes.

## Probe

```php
return array(
    'acpt' => defined( 'ACPT_PLUGIN_VERSION' ) ? ACPT_PLUGIN_VERSION : ( class_exists( 'ACPT\Core\ACPT_Plugin' ) ? 'active' : null ),
);
```

## List registered types

```php
return array(
    'post_types'  => get_post_types( array( '_builtin' => false ), 'objects' ),
    'taxonomies'  => get_taxonomies( array( '_builtin' => false ), 'objects' ),
);
```

Filter results to those registered by ACPT when identifiable via ACPT export or naming conventions.

## Meta groups & options

- Read ACPT meta group definitions from plugin storage or exported JSON before creating fields.
- Options pages use ACPT-specific option keys — never guess key names.

## Workflow

1. Prefer ACPT UI on staging for structural changes; export config for version control when available.
2. Use `register_post_type` in sandbox only when ACPT export is not available and the user approves code-first registration.

## Verification

Confirm CPT appears in admin menu, meta boxes render, and sample post saves meta correctly.
