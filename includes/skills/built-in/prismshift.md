---
name: prismshift
description: Manage PrismShift SEO titles, meta descriptions, focus keywords, site settings, audits, and AI-assisted optimization on a LayrShift site. Use for PrismShift, on-page SEO, or when Yoast/Rank Math are not the target plugin.
enable_prompt: true
enable_agentic: true
---

# PrismShift via LayrShift

Prefer **`layrshift/prismshift-*` abilities** for post and site SEO reads/writes. Use `execute-php` only when PrismShift does not expose the data you need.

## Start here

1. `layrshift/skill-get` → `prismshift`
2. `layrshift/prismshift-get-site-settings` for global context
3. `layrshift/prismshift-get-post-seo` before edits
4. `layrshift/prismshift-analyze-post-seo` for on-page checks
5. `layrshift/prismshift-update-post-seo` on drafts; `allow_publish: true` only with user approval
6. `layrshift/prismshift-ai-optimize-post` for suggestions (`apply: true` only after user approval)

## Abilities

| Step | Ability |
|------|---------|
| Read post SEO | `layrshift/prismshift-get-post-seo` |
| Update post SEO | `layrshift/prismshift-update-post-seo` |
| Analyze post | `layrshift/prismshift-analyze-post-seo` |
| AI suggestions | `layrshift/prismshift-ai-optimize-post` |
| Read site settings | `layrshift/prismshift-get-site-settings` |

## Fields

| Input key | PrismShift meta |
|-----------|-----------------|
| `seo_title` | `_prismshift_title` |
| `meta_description` | `_prismshift_description` |
| `focus_keyword` | `_prismshift_focus_keyword` |
| `noindex` | `_prismshift_noindex` |
| `canonical` | `_prismshift_canonical` |
| `schema_type` | `_prismshift_schema_type` |

## Rules

- Draft-first for post SEO updates
- Re-read with `prismshift-get-post-seo` after every write
- PrismShift must be active (`PRISMSHIFT_VERSION` defined)
