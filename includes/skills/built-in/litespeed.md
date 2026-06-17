---
name: litespeed
description: Manage LiteSpeed Cache purge and settings on a LayrShift site. Use for LiteSpeed Cache, LSCache, object cache, or CDN purge workflows.
enable_prompt: true
enable_agentic: true
---

# LiteSpeed Cache via LayrShift

Prefer **`layrshift/litespeed-*` abilities** when LiteSpeed Cache is active.

## Start here

1. `layrshift/skill-get` → `litespeed`
2. `layrshift/litespeed-get-status`
3. `layrshift/litespeed-get-settings`
4. `layrshift/litespeed-purge-all` — after code or content deploys

## Abilities

| Step | Ability |
|------|---------|
| Status | `layrshift/litespeed-get-status` |
| Settings | `layrshift/litespeed-get-settings` |
| Purge all | `layrshift/litespeed-purge-all` |

## Rules

- Purge is destructive for cached HTML — confirm on production-like staging
- On LiteSpeed Web Server hosts, purge may also run via server QUIC.cloud integration
- Re-check a key URL after purge
