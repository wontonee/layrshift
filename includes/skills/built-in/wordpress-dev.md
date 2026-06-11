---
name: wordpress-dev
description: Core WordPress development on a LayrShift-connected dev/staging site. Use when building or fixing themes, plugins, hooks, templates, options, queries, or sandbox PHP. Triggers for "add a hook", "fix this template", "create a mu-plugin", "debug WordPress", or any filesystem/PHP dev task on the MCP site.
enable_prompt: true
enable_agentic: true
---

# WordPress Dev via LayrShift

Implementor playbook for LayrSoft AI development work on the connected site.

## Start Here

1. Call `layrshift/execute-php` to learn active theme, parent theme, active plugins, and relevant options
2. Use `layrshift/list-directory` to map the theme/plugin file tree before editing
3. Never guess paths — read the actual `stylesheet_directory` and `template_directory`

## Probe template

```php
return array(
    'site_url'      => site_url(),
    'theme'         => wp_get_theme()->get( 'Name' ),
    'theme_slug'    => get_stylesheet(),
    'parent'        => wp_get_theme()->parent() ? wp_get_theme()->parent()->get( 'Name' ) : null,
    'active_plugins' => get_option( 'active_plugins' ),
);
```

## File operations

| Goal | Ability |
|---|---|
| Read source | `layrshift/read-file` |
| Create/overwrite | `layrshift/write-file` |
| Surgical edit | `layrshift/edit-file` (exact string match) |
| Remove | `layrshift/delete-file` |
| Browse | `layrshift/list-directory` |
| WP-CLI | `layrshift/run-wp-cli`, `layrshift/get-wp-cli-job` |
| Browser admin | `layrshift/create-admin-access-link` |

## Gutenberg block editor

For posts/pages using static Gutenberg blocks:

1. `layrshift/gutenberg-get-finalizer-runtime` — check Block Editor Queue is online
2. `layrshift/gutenberg-get-content` — read live content + pending summary
3. `layrshift/gutenberg-add-pending-change` — queue block replacements
4. `layrshift/gutenberg-enable-batch-finalization` — mark batch ready
5. Keep `admin.php?page=layrshift-gutenberg-finalize` open until batch is `finalized`

Use `layrshift/gutenberg-list-pending-batches` and `layrshift/gutenberg-get-pending-batch` to monitor status.

PHP files you create must go to `wp-content/layrshift-sandbox/`. Theme and plugin PHP edits use `edit-file` on the real paths.

## Sandbox PHP

Deploy experimental code to the sandbox, not the active theme:

1. `layrshift/write-file` → `wp-content/layrshift-sandbox/my-script.php`
2. Verify with `layrshift/execute-php`
3. If it breaks the site: `layrshift/disable-file` or visit `?layrshift-safe-mode=1`

Keep sandbox files minimal. Guard all plugin-specific logic with `class_exists()` / `function_exists()`.

## WordPress-native rules

- Use CPTs, taxonomies, post meta, and options — not hardcoded PHP arrays for content
- If ACF, JetEngine, Pods, or WooCommerce is active, use their APIs for data they own
- Prefix all functions and hooks with the project slug
- Escape output, sanitize input, verify nonces on admin actions
- Enqueue assets conditionally — never load on every page unconditionally

## Verification

After every change:

1. Re-read the edited file with `layrshift/read-file`, or
2. Run a targeted `layrshift/execute-php` probe (hook registered, option saved, query returns expected count)

Report what changed, how you verified it, and any manual follow-up.
