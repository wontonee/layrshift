---
name: wp-fastest-cache
description: Manage WP Fastest Cache HTML and minified asset cache on a LayrShift site. Use for WP Fastest Cache, cache clear, or minify settings.
enable_prompt: true
enable_agentic: true
---

# WP Fastest Cache via LayrShift

Prefer **`layrshift/wp-fastest-cache-*` abilities** when WP Fastest Cache is active.

## Start here

1. `layrshift/skill-get` → `wp-fastest-cache`
2. `layrshift/wp-fastest-cache-get-status`
3. `layrshift/wp-fastest-cache-get-settings`
4. `layrshift/wp-fastest-cache-clear-cache` — after deploys

## Abilities

| Step | Ability |
|------|---------|
| Status | `layrshift/wp-fastest-cache-get-status` |
| Settings | `layrshift/wp-fastest-cache-get-settings` |
| Clear cache | `layrshift/wp-fastest-cache-clear-cache` |

## Rules

- Clearing cache removes static HTML — confirm on shared staging
- Mobile cache and browser cache flags are in settings ability
- Test one cached URL after clear
