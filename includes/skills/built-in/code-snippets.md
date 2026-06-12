---
name: code-snippets
description: Add and manage PHP, HTML, CSS, and JS snippets with Code Snippets or WPCode on a LayrShift site. Use for hooks, filters, small logic, or safe snippet validation workflows.
enable_prompt: true
enable_agentic: true
---

# Code Snippets via LayrShift

Prefer the site's snippet plugin over editing `functions.php` when the user already uses Code Snippets or WPCode.

## Probe

```php
return array(
    'code_snippets' => defined( 'CODE_SNIPPETS_VERSION' ) ? CODE_SNIPPETS_VERSION : null,
    'wpcode'        => defined( 'WPCODE_VERSION' ) ? WPCODE_VERSION : null,
);
```

## Code Snippets plugin

Snippets are stored as `code_snippet` posts:

```php
$snippets = get_posts( array(
    'post_type'      => 'code_snippet',
    'posts_per_page' => -1,
    'post_status'    => array( 'publish', 'draft' ),
) );
return wp_list_pluck( $snippets, 'post_title', 'ID' );
```

Create snippets as **draft/inactive** first. Activate only after user review.

## WPCode

WPCode uses `wpcode` post type and custom tables in some versions — list posts and read plugin docs for the installed major version.

## Safe validate flow

1. Write snippet code to sandbox PHP and run via `layrshift/execute-php` to validate syntax/logic.
2. Copy validated code into snippet plugin as inactive snippet.
3. Ask user to activate and test one admin/front request.

## LayrShift sandbox alternative

For experimental hooks without a snippet plugin, use `wp-content/layrshift-sandbox/` with `layrshift/write-file` and safe mode fallback (`?layrshift-safe-mode=1`).

## Verification

Confirm snippet appears in plugin UI, runs on intended hook/condition, and deactivates cleanly.
