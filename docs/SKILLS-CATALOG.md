# LayrShift Built-in Skills Catalog

**Status:** Shipped in plugin (`includes/skills/built-in/*.md`)  
**Parity target:** [Novamira Pro specializations](https://novamira.ai/pro/) (17 builders/plugins + core playbooks)

LayrShift skills are Markdown playbooks the agent loads via `layrshift/skill-get`. They appear in MCP instructions under **Available Skills** when `enable_agentic: true`.

## How skills differ from Novamira Pro

| | Novamira Pro | LayrShift (today) |
|---|---|---|
| **Delivery** | Pro plugin + dedicated MCP abilities per plugin | Built-in `SKILL.md` playbooks |
| **Execution** | Native abilities (e.g. Elementor element tree APIs) | `execute-php`, filesystem, Gutenberg queue, Elementor document abilities |
| **Memory** | Project memory across sessions | Not implemented yet |
| **Custom skills** | WordPress CPT + admin UI | `layrshift/skill-write` + CPT |

**Plugin MCP abilities (shipped):** When the target plugin is active, LayrShift registers typed abilities under matching Abilities Hub categories. Each has a built-in skill playbook.

| Category | Abilities (when plugin active) | Skill |
|----------|-------------------------------|-------|
| Elementor | `elementor-get-document`, `elementor-save-document`, `elementor-list-templates` | `elementor` |
| Yoast SEO | `yoast-get-post-seo`, `yoast-update-post-seo`, `yoast-get-site-settings` | `yoast` |
| Smush | `smush-get-stats`, `smush-list-unsmushed`, `smush-run-bulk-smush` | `smush` |
| VaultShift | `vaultshift-get-status`, `vaultshift-trigger-scan`, `vaultshift-list-activity` | `vaultshift` |
| BlogiBot | `blogibot-get-status`, `blogibot-list-posts`, `blogibot-get-settings` | `blogibot` |

Future: dedicated abilities for other builders (Divi, Bricks, etc.) can be added alongside skills.

## Depth tiers

Skills are grouped by how much procedural detail they carry for agents:

| Tier | Slugs | Notes |
|------|-------|-------|
| **Core** | `wordpress-dev`, `gutenberg-edit-content`, `skill-creator`, `template-studio` | Site-wide workflows and authoring |
| **Builder (deep)** | `divi`, `elementor`, `bricks`, `breakdance`, `wpbakery` | ~120–200 line playbooks: probe, version gate, read/write tree, verification |
| **Builder (standard)** | `etch`, `mosaic` | Expanded playbooks; thinner than P1/P2 builders |
| **Field / store** | `woocommerce`, `acf`, `jetengine`, `meta-box`, `pods`, `acpt`, `ase` | Meta and CPT workflows |
| **Theme** | `generatepress`, `kadence` | Theme-specific hooks and options |
| **Code** | `code-snippets` | Snippet plugin workflows |

**Divi 5 note:** The `divi` skill is **Divi 5–first** — block-module trees in `post_content`, Theme Builder layout post IDs, loops, dynamic content, and presets. Legacy `[et_pb_` shortcode-only pages are documented as a migration/manual path, not parity target.

## Core playbooks

| Slug | Purpose |
|------|---------|
| `wordpress-dev` | Theme/plugin PHP, hooks, sandbox, filesystem |
| `gutenberg-edit-content` | Block Editor Queue workflow for native blocks |
| `skill-creator` | Author new LayrShift skills |
| `template-studio` | Gemini REST page generation (MCP/agents; UI deferred) |

## Builder specializations

| Slug | Novamira Pro equivalent | Depth |
|------|-------------------------|-------|
| `elementor` | Elementor | Deep skill + 3 MCP abilities when Elementor active |
| `bricks` | Bricks | Deep (templates, components, `\Bricks\Database`) |
| `divi` | Divi 5 | Deep (module tree, Theme Builder, loops, dynamic) |
| `breakdance` | Breakdance | Deep (tokens, popups, conditions) |
| `wpbakery` | WPBakery Page Builder | Deep (shortcode nesting, grid templates) |
| `etch` | Etch | Standard |
| `mosaic` | Mosaic | Standard |

## Theme specializations

| Slug | Novamira Pro equivalent |
|------|-------------------------|
| `generatepress` | GeneratePress |
| `kadence` | Kadence |

## Store & field plugins

| Slug | Novamira Pro equivalent |
|------|-------------------------|
| `woocommerce` | WooCommerce |
| `acf` | ACF |
| `jetengine` | JetEngine |
| `meta-box` | Meta Box |
| `pods` | Pods |
| `acpt` | ACPT |
| `ase` | ASE |

## Code

| Slug | Novamira Pro equivalent |
|------|-------------------------|
| `code-snippets` | Code Snippets |

## Site plugin integrations

| Slug | Plugin | MCP abilities |
|------|--------|---------------|
| `yoast` | Yoast SEO | 3 abilities (post SEO + site settings) |
| `smush` | Smush | 3 abilities (stats, list, bulk) |
| `vaultshift` | VaultShift | 3 abilities (status, scan, activity) |
| `blogibot` | BlogiBot | 3 abilities (status, posts, settings) |

## Agent workflow

1. `mcp-adapter/discover-abilities` — read instructions + skill catalog
2. Match user request to a skill `description`
3. `layrshift/skill-get` with slug
4. Follow playbook using LayrShift abilities

## Adding skills

- **Built-in:** add `includes/skills/built-in/{slug}.md` with YAML frontmatter
- **Per-site:** `layrshift/skill-write` or LayrShift Skills CPT (when admin UI ships)

See `skill-creator` built-in skill for format.
