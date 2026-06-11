---
name: skill-creator
description: Guidance for creating and refining LayrShift skills — single-document markdown playbooks stored in WordPress. Use when the user asks to create a skill, make a playbook for X, add agent knowledge, or refine an existing skill.
enable_prompt: true
enable_agentic: true
---

# Skill Creator

Guidance for creating effective LayrShift skills.

## What a LayrShift Skill Is

A single Markdown document — frontmatter plus body — stored in WordPress. When its `description` matches the user's request, the agent loads the body via `layrshift/skill-get`.

Skills are **flat**: no bundled scripts or reference directories. Everything lives in one body (1 MB max; aim for under 5,000 words).

### Anatomy

```
---
name: <slug>
description: <one-line trigger blurb>
enable_prompt: true|false
enable_agentic: true|false
---

<Markdown body>
```

## Abilities

| Goal | Ability |
|---|---|
| Create or replace | `layrshift/skill-write` |
| Patch fields | `layrshift/skill-edit` |
| Read back | `layrshift/skill-get` |
| Remove | `layrshift/skill-delete` |

## Workflow

1. Ask the user for 1–3 example requests the skill should handle
2. Identify procedural knowledge the agent cannot derive from the site alone
3. Call `layrshift/skill-write` with `title`, `description`, `content`
4. Verify with `layrshift/skill-get`
5. Iterate with `layrshift/skill-edit` after user feedback

## Writing the description

The description is the **only** field the agent reads to decide whether to load a skill. Include concrete trigger phrases.

- Bad: `"Helps with posts."`
- Good: `"Bulk-update WooCommerce product excerpts after import. Use when the user mentions stale excerpts, bulk product text fixes, or post-import cleanup."`

## What belongs in the body

**Include:** business rules, naming conventions, step-by-step fragile operations, templates, pointers to specific `layrshift/*` abilities.

**Exclude:** generic WordPress tutorials, meta sections like "When to Use This Skill", changelogs, installation docs.

## Conflict handling

`on_conflict` for `skill-write`:

- `fail` (default) — error with suggested free slug
- `rename` — append `-2`, `-3`, etc.
- `replace` — overwrite existing **user** skill (not built-ins)
