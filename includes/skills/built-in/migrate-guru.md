---
name: migrate-guru
description: Monitor Migrate Guru site migration connection and migration state on a LayrShift site. Use for Migrate Guru, site migration, clone, or move workflows.
enable_prompt: true
enable_agentic: true
---

# Migrate Guru via LayrShift

Prefer **`layrshift/migrate-guru-*` abilities** for migration readiness checks. **Do not start migrations via MCP** — use Migrate Guru admin or BlogVault dashboard.

## Start here

1. `layrshift/skill-get` → `migrate-guru`
2. `layrshift/migrate-guru-get-status` — plugin version and connection
3. `layrshift/migrate-guru-get-connection-info` — masked key + account summary
4. `layrshift/migrate-guru-get-migration-state` — in-progress flag and recent options

## Abilities

| Step | Ability |
|------|---------|
| Status | `layrshift/migrate-guru-get-status` |
| Connection info | `layrshift/migrate-guru-get-connection-info` |
| Migration state | `layrshift/migrate-guru-get-migration-state` |

## Rules

- Read-only monitoring — migrations are destructive and long-running
- Never log or echo raw connection keys
- Before migration, verify both source and destination URLs with the user
- After migration, re-run status abilities on the destination site
