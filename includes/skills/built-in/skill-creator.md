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

## Builder playbook template (~120–200 lines)

Use this outline for page-builder and theme-builder skills (`elementor`, `divi`, `bricks`, etc.). Each section should include runnable `execute-php` snippets where possible.

```markdown
# {Builder} via LayrShift

One-paragraph positioning: storage format, what NOT to do (e.g. no HTML blobs).

## Start here
Numbered agent checklist (load skill, probe, draft-first, re-read tree).

## Probe environment
PHP snippet: version constants, active check, bail message.

## Version / format gate (if applicable)
Table: primary path vs legacy path; probe snippet (blocks vs shortcodes vs JSON meta).

## Content model
Hierarchy (section → row → column → module, or builder equivalent).
Where data lives (`post_content`, `_elementor_data`, `_bricks_*`, etc.).
Read-tree PHP snippet.

## Write workflow
Duplicate → read → edit tree → save → clear cache → re-read.

## Common tasks
### Task A (e.g. hero section)
### Task B (e.g. single module edit)
### Task C (e.g. query loop)
### Task D (e.g. dynamic content)
### Task E (e.g. global presets / site templates)

## Theme templates / headers (if applicable)
CPT discovery, layout post IDs, assignment flow.

## Child theme and custom code
`read-file` / `edit-file` paths; sandbox for experiments.

## LayrShift abilities map
Table mapping steps to `execute-php`, `read-file`, `create-admin-access-link`, etc.

## Rules (non-negotiable)
Draft-first, no publish without user, no fake HTML layouts, staging only.

## Verification checklist
Checkbox list agents can run through before reporting done.

## Failure modes
Table: symptom → action (cache, wrong post ID, safe mode).
```

### Builder depth checklist

Before shipping or updating a builder skill, confirm:

- [ ] `description` includes 5+ concrete trigger phrases (product name, UI labels, shortcode prefixes)
- [ ] Probe snippet returns version + active state; agent stops if inactive
- [ ] Primary vs legacy storage format documented with detection snippet
- [ ] Read path uses real meta keys / block format for that builder
- [ ] Write path is tree/meta edits, not front-end HTML paste
- [ ] At least 3 task-specific workflows (hero, single edit, loops/dynamic/templates)
- [ ] Global/site-wide edits point to correct post type (kit, Theme Builder layout, Bricks template)
- [ ] LayrShift abilities table present
- [ ] Verification + failure modes sections included
- [ ] Under ~5,000 words; no generic WordPress tutorial filler
