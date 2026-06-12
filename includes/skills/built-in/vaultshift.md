---
name: vaultshift
description: Monitor VaultShift security score, schedule malware scans, and read activity logs on a LayrShift site. Use for VaultShift, security scan, WAF, firewall, or site hardening tasks.
enable_prompt: true
enable_agentic: true
---

# VaultShift via LayrShift

Prefer **`layrshift/vaultshift-*` abilities** for security operations. VaultShift admin UI remains the source of truth for quarantine and firewall rule edits.

## Start here

1. `layrshift/skill-get` → `vaultshift`
2. `layrshift/vaultshift-get-status` — score, WAF mode, last scan summary
3. `layrshift/vaultshift-list-activity` — recent security events
4. `layrshift/vaultshift-trigger-scan` — schedule scan (confirm with user on production)

## Abilities

| Step | Ability |
|------|---------|
| Security status | `layrshift/vaultshift-get-status` |
| Schedule scan | `layrshift/vaultshift-trigger-scan` |
| Activity log | `layrshift/vaultshift-list-activity` |

## Rules

- Read-only monitoring by default; scans are async
- Do not disable WAF or hardening via `execute-php` without explicit user approval
- For restore/backup operations, use VaultShift admin or document manual steps
