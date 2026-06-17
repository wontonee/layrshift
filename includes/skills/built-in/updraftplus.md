---
name: updraftplus
description: Inspect UpdraftPlus backup history, schedule, and settings on a LayrShift dev/staging site. Use for backups, restore planning, or backup health before changes.
enable_prompt: true
enable_agentic: true
---

# UpdraftPlus via LayrShift

Prefer **`layrshift/updraftplus-*` abilities** when UpdraftPlus is active. All abilities are read-only.

## Start here

1. `layrshift/skill-get` → `updraftplus`
2. `layrshift/updraftplus-get-status` — version and last backup
3. `layrshift/updraftplus-list-backups` — recent backup sets
4. `layrshift/updraftplus-get-settings` — schedule and retention

## Abilities

| Step | Ability |
|------|---------|
| Backup status | `layrshift/updraftplus-get-status` |
| Backup history | `layrshift/updraftplus-list-backups` |
| Schedule settings | `layrshift/updraftplus-get-settings` |

## Rules

- Read-only — do not trigger backups or restores via abilities without explicit user approval
- Never return remote storage credentials or API keys
- Recommend a fresh backup before large LayrShift agent changes on staging
