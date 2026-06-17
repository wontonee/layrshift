---
name: wordfence
description: Inspect Wordfence firewall, scan, and security settings on a LayrShift dev/staging site. Use for Wordfence status, malware scan, firewall, or Application Password blocks.
enable_prompt: true
enable_agentic: true
---

# Wordfence via LayrShift

Prefer **`layrshift/wordfence-*` abilities** for read-only security introspection. All abilities are read-only in this integration.

## Start here

1. `layrshift/skill-get` → `wordfence`
2. `layrshift/wordfence-get-status`
3. `layrshift/wordfence-get-scan-summary`
4. `layrshift/wordfence-get-settings-summary`

## Abilities

| Step | Ability |
|------|---------|
| Firewall status | `layrshift/wordfence-get-status` |
| Scan summary | `layrshift/wordfence-get-scan-summary` |
| Settings summary | `layrshift/wordfence-get-settings-summary` |

## Application Passwords

If LayrShift MCP auth fails, Wordfence may block Application Passwords. LayrShift already surfaces this in admin — check `disable_application_passwords` in settings summary. Do not change Wordfence settings without user approval.

## Rules

- Read-only — do not trigger scans or change firewall rules via abilities
- Never return license keys or API secrets
- Confirm with user before any security setting changes via `execute-php`
