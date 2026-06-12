---
name: smush
description: Manage Smush image optimization, bulk smush, and media stats on a LayrShift site. Use for Smush, image compression, bulk optimize, unsmushed images, or WebP/lazy-load settings.
enable_prompt: true
enable_agentic: true
---

# Smush via LayrShift

Prefer **`layrshift/smush-*` abilities** for optimization workflows.

## Start here

1. `layrshift/skill-get` → `smush`
2. `layrshift/smush-get-stats` — current stats and settings
3. `layrshift/smush-list-unsmushed` — backlog before bulk runs
4. `layrshift/smush-run-bulk-smush` — only after user confirms on large libraries

## Abilities

| Step | Ability |
|------|---------|
| Stats & settings | `layrshift/smush-get-stats` |
| List pending images | `layrshift/smush-list-unsmushed` |
| Bulk optimize | `layrshift/smush-run-bulk-smush` |

## Rules

- Confirm with user before bulk smush on production-sized media libraries
- Re-check stats after bulk runs
- Staging scope for destructive media operations
