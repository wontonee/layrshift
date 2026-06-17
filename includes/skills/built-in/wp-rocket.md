---
name: wp-rocket
description: Manage WP Rocket page cache, minify, and preload on a LayrShift dev/staging site. Use for WP Rocket, cache purge, performance tuning, or rocket settings.
enable_prompt: true
enable_agentic: true
---

# WP Rocket via LayrShift

Prefer **`layrshift/wp-rocket-*` abilities** when WP Rocket is active.

## Start here

1. `layrshift/skill-get` → `wp-rocket`
2. `layrshift/wp-rocket-get-status` — version and feature flags
3. `layrshift/wp-rocket-get-settings` — safe config snapshot
4. `layrshift/wp-rocket-clear-cache` — purge after deploys (confirm on shared staging)

## Abilities

| Step | Ability |
|------|---------|
| Status | `layrshift/wp-rocket-get-status` |
| Settings | `layrshift/wp-rocket-get-settings` |
| Clear cache | `layrshift/wp-rocket-clear-cache` |

## Rules

- Dev/staging only — confirm before clearing cache on shared environments
- Never expose license keys; abilities return safe settings only
- After theme/plugin deploys, clear cache then verify front-end in browser
