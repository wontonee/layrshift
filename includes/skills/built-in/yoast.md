---
name: yoast
description: Manage Yoast SEO titles, meta descriptions, focus keywords, robots, and site SEO settings on a LayrShift site. Use for Yoast SEO, SEO title, meta description, focus keyphrase, or noindex tasks.
enable_prompt: true
enable_agentic: true
---

# Yoast SEO via LayrShift

Prefer **`layrshift/yoast-*` abilities** for post and site SEO reads/writes. Use `execute-php` only for probes Yoast does not expose.

## Start here

1. `layrshift/skill-get` → `yoast`
2. `layrshift/yoast-get-site-settings` for global context
3. `layrshift/yoast-get-post-seo` before edits
4. `layrshift/yoast-update-post-seo` on drafts; `allow_publish: true` only with user approval

## Abilities

| Step | Ability |
|------|---------|
| Read post SEO | `layrshift/yoast-get-post-seo` |
| Update post SEO | `layrshift/yoast-update-post-seo` |
| Read site settings | `layrshift/yoast-get-site-settings` |

## Fields

| Input key | Yoast meta |
|-----------|------------|
| `seo_title` | `_yoast_wpseo_title` |
| `meta_description` | `_yoast_wpseo_metadesc` |
| `focus_keyword` | `_yoast_wpseo_focuskw` |
| `noindex` | `_yoast_wpseo_meta-robots-noindex` |

## Rules

- Draft-first for post SEO updates
- Re-read with `yoast-get-post-seo` after every write
- Do not bypass Yoast with raw HTML `<title>` / `<meta>` in theme templates for content SEO
