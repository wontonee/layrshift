---
name: wp-optimize
description: Manage WP-Optimize page cache, minify, and database optimization settings on a LayrShift site. Use for WP-Optimize, cache flush, or performance cleanup.
enable_prompt: true
enable_agentic: true
---

# WP-Optimize via LayrShift

Prefer **`layrshift/wp-optimize-*` abilities** when WP-Optimize is active.

## Start here

1. `layrshift/skill-get` → `wp-optimize`
2. `layrshift/wp-optimize-get-status`
3. `layrshift/wp-optimize-get-settings`
4. `layrshift/wp-optimize-purge-cache` — after deploys

## Abilities

| Step | Ability |
|------|---------|
| Status | `layrshift/wp-optimize-get-status` |
| Settings | `layrshift/wp-optimize-get-settings` |
| Purge cache | `layrshift/wp-optimize-purge-cache` |

## Rules

- Database cleanup abilities are not exposed — use WP-Optimize admin for destructive DB tasks
- Confirm cache purge on shared staging before running
- Verify minify settings before purging CSS/JS during active front-end QA
