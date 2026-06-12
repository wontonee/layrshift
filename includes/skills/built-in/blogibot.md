---
name: blogibot
description: Manage BlogiBot-generated posts, settings, and publishing status on a LayrShift site. Use for BlogiBot, AI blog posts, automated content, or BlogiBot drafts.
enable_prompt: true
enable_agentic: true
---

# BlogiBot via LayrShift

When BlogiBot is active, use **`layrshift/blogibot-*` abilities** first.

## Start here

1. `layrshift/skill-get` → `blogibot`
2. `layrshift/blogibot-get-status` — version and detected post types
3. `layrshift/blogibot-list-posts` — recent BlogiBot content
4. `layrshift/blogibot-get-settings` — plugin options

## Abilities

| Step | Ability |
|------|---------|
| Probe plugin | `layrshift/blogibot-get-status` |
| List content | `layrshift/blogibot-list-posts` |
| Read settings | `layrshift/blogibot-get-settings` |

## Rules

- If BlogiBot is inactive, abilities are not registered — ask user to activate the plugin
- Draft-first for content edits; use Gutenberg or builder skills for layout changes
- Probe custom post types via `blogibot-get-status` before assuming `post`
