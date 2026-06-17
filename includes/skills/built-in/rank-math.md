---
name: rank-math
description: Manage Rank Math SEO titles, meta descriptions, focus keywords, robots, and site SEO settings on a LayrShift site. Use for Rank Math SEO, SEO title, meta description, focus keyword, or noindex tasks.
enable_prompt: true
enable_agentic: true
---

# Rank Math SEO via LayrShift

Prefer **`layrshift/rank-math-*` abilities** for post and site SEO reads/writes. Use `execute-php` only for probes Rank Math does not expose.

## Start here

1. `layrshift/skill-get` → `rank-math`
2. `layrshift/rank-math-get-site-settings` for global context
3. `layrshift/rank-math-get-post-seo` before edits
4. `layrshift/rank-math-update-post-seo` on drafts; `allow_publish: true` only with user approval

## Abilities

| Step | Ability |
|------|---------|
| Read post SEO | `layrshift/rank-math-get-post-seo` |
| Update post SEO | `layrshift/rank-math-update-post-seo` |
| Read site settings | `layrshift/rank-math-get-site-settings` |

## Fields

| Input key | Rank Math meta |
|-----------|----------------|
| `seo_title` | `rank_math_title` |
| `meta_description` | `rank_math_description` |
| `focus_keyword` | `rank_math_focus_keyword` |
| `robots` | `rank_math_robots` (array) |

## Rules

- Draft-first for post SEO updates
- Re-read with `rank-math-get-post-seo` after every write
- Do not bypass Rank Math with raw HTML `<title>` / `<meta>` in theme templates for content SEO
