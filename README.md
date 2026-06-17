<p align="center">
  <img src="assets/banner.svg" alt="LayrShift — MCP server for WordPress dev and staging" width="100%" />
</p>

# LayrShift

**Forge WordPress with your AI agent.** LayrShift is an MCP (Model Context Protocol) server plugin for WordPress that lets tools like Cursor, Claude Code, and VS Code Copilot read and write your site, run sandboxed PHP, manage Gutenberg batches, and integrate with popular plugins — all through a authenticated HTTP API.

> **Distribution:** LayrShift is **not** listed on the [WordPress.org plugin directory](https://wordpress.org/plugins/). Like other powerful agent plugins (e.g. Novamira), source lives on GitHub; **install only from the official release zip** because of its security profile (filesystem access, optional code execution, administrator-level automation). **Latest:** [layrshift-1.0.6.zip](https://wontonee-micro-services.s3.us-east-1.amazonaws.com/layrshift/layrshift-1.0.6.zip)

> **Environment:** **Development and staging sites only.** Do not install on production.

| | |
|---|---|
| **Version** | 1.0.6 |
| **Requires** | WordPress 6.9+, PHP 8.0+ |
| **License** | [GPL-2.0-or-later](LICENSE) |
| **Author** | [Saju Gopal / Wontonee DigitalCraft LLP](https://wontonee.com) |
| **Built on** | [WordPress MCP Adapter](https://github.com/WordPress/mcp-adapter) + [Abilities API](https://make.wordpress.org/core/2025/11/10/abilities-api/) |

---

## Table of contents

- [Why GitHub, not WordPress.org?](#why-github-not-wordpressorg)
- [LayrShift vs Novamira](#layrshift-vs-novamira)
- [What LayrShift does](#what-layrshift-does)
- [Requirements](#requirements)
- [Installation](#installation)
- [Quick start](#quick-start)
- [Connect your MCP client](#connect-your-mcp-client)
- [Admin interface](#admin-interface)
- [Abilities reference](#abilities-reference)
- [Plugin integrations](#plugin-integrations)
- [Agent skills](#agent-skills)
- [Security model](#security-model)
- [Troubleshooting](#troubleshooting)
- [Safe mode & crash recovery](#safe-mode--crash-recovery)
- [Changelog](#changelog)
- [Support & contributing](#support--contributing)

---

## Why GitHub, not WordPress.org?

WordPress.org’s plugin review is designed for code that runs safely on millions of shared hosting sites. LayrShift is intentionally powerful:

- Filesystem read, write, edit, and delete under the site root
- Optional **execute PHP** and **WP-CLI** abilities (GitHub / full dev builds)
- Signed upload URLs for plugins, themes, and media
- Persistent sandbox PHP with autoload

That power is appropriate for **local dev, staging, and agency workflows** where an administrator explicitly opts in. It is not appropriate as a one-click install for every production site. We publish versioned release zips on S3 so you can review the package, pin a version, and install when you understand the risk.

**AI Abilities are disabled by default** until an administrator acknowledges the warning and enables them in Settings.

---

## LayrShift vs Novamira

LayrShift shares architectural DNA with [Novamira](https://novamira.ai) — both are MCP server plugins built on the WordPress Abilities API and MCP Adapter, aimed at **dev/staging** sites with filesystem and code-execution powers. Neither is distributed on WordPress.org for security reasons.

LayrShift is an **open-source (GPL)** GitHub distribution with **more built-in MCP tools and agent skills out of the box**. Novamira is **AGPL** with a commercial **Novamira Pro** tier that adds native abilities per page builder and plugin.

### At a glance

| | **LayrShift** | **Novamira** |
|---|:---:|:---:|
| **Distribution** | Free — [download zip](https://wontonee-micro-services.s3.us-east-1.amazonaws.com/layrshift/layrshift-1.0.6.zip) | [novamira.ai](https://novamira.ai) (license / Pro) |
| **WordPress.org directory** | No | No |
| **License** | GPL-2.0-or-later | AGPL-3.0 (core) |
| **Target environment** | Dev / staging only | Dev / staging only |
| **MCP transport** | HTTP + Application Password | HTTP + Application Password |
| **Core filesystem + sandbox abilities** | Yes | Yes |
| **Execute PHP** | Yes (full / dev builds) | Yes |
| **WP-CLI abilities** | Yes (full / dev builds) | Yes |
| **Gutenberg Block Editor Queue** | Yes (11 abilities) | Yes (11 abilities) |
| **Plugin-specific MCP abilities** | **15** (5 integrations × 3) | Via **Novamira Pro** (per specialization) |
| **Built-in agent skill playbooks** | **25** | **2** in OSS core; more via Pro |
| **MCP skill prompts** | One per built-in skill (~25) | One per registered skill |
| **Project memory (cross-session)** | No | Yes (Novamira) |
| **Novamira Visual workspace** | No | Yes (separate product) |
| **Abilities Hub (per-tool toggle)** | Yes | Yes |
| **Activity log** | Yes | Yes |

### MCP abilities checklist

Counts are **registered MCP tools** when the relevant plugins are active and abilities are enabled. Novamira Pro abilities depend on your license; the free Novamira core matches the “shared core” rows.

| Category | Ability (examples) | LayrShift | Novamira core | Novamira Pro |
|----------|-------------------|:---------:|:-------------:|:------------:|
| **Filesystem** | read / write / edit / delete file | ✅ | ✅ | ✅ |
| | list directory | ✅ | ✅ | ✅ |
| | create upload link | ✅ | ✅ | ✅ |
| | sandbox enable / disable file | ✅ | ✅ | ✅ |
| **Code execution** | execute PHP | ✅ | ✅ | ✅ |
| | run WP-CLI (+ async job poll) | ✅ | ✅ | ✅ |
| **Admin** | create admin access link | ✅ | ✅ | ✅ |
| **Gutenberg** | get / write content | ✅ | ✅ | ✅ |
| | pending batch queue (add, list, finalize, …) | ✅ (11 tools) | ✅ (11 tools) | ✅ |
| **Elementor** | get / save document, list templates | ✅ | — | ✅ (native) |
| **Yoast SEO** | get / update post SEO, site settings | ✅ | — | ✅ (native) |
| **Smush** | stats, list unsmushed, bulk smush | ✅ | — | ✅ (native) |
| **VaultShift** | status, trigger scan, activity log | ✅ | — | ✅ (native) |
| **BlogiBot** | status, list posts, settings | ✅ | — | ✅ (native) |
| **Skills API** | skill-get / write / edit / delete | ✅ | ✅ | ✅ |
| **Discovery** | discover-abilities (+ instructions) | ✅ | ✅ | ✅ |

**LayrShift totals (typical dev site):** ~**40+ MCP tools** from core + Gutenberg + integrations, plus ~**25 skill prompts**.  
**Novamira OSS core:** ~**27 MCP tools** (core + Gutenberg + skills API). Pro adds **dedicated tools per builder/plugin** on top.

### Built-in specializations (agent skills)

Skills are Markdown playbooks loaded via `layrshift/skill-get` (or `novamira/skill-get`). They teach the agent how to work with each stack. LayrShift ships **all of these in the plugin**; Novamira OSS ships **2**; Novamira Pro covers the builder/plugin column via **native abilities + Pro skills**.

| Skill slug | Topic | LayrShift | Novamira OSS | Novamira Pro |
|------------|-------|:---------:|:------------:|:------------:|
| `wordpress-dev` | Theme/plugin PHP, hooks, sandbox | ✅ | — | ✅ |
| `gutenberg-edit-content` | Block Editor Queue workflow | ✅ | ✅ | ✅ |
| `skill-creator` | Author custom skills | ✅ | ✅ | ✅ |
| `template-studio` | AI page generation (Gemini) | ✅ | — | ✅ |
| `elementor` | Elementor trees + documents | ✅ skill + **3 MCP tools** | — | ✅ native |
| `bricks` | Bricks builder | ✅ | — | ✅ |
| `divi` | Divi 5 modules & Theme Builder | ✅ | — | ✅ |
| `breakdance` | Breakdance | ✅ | — | ✅ |
| `wpbakery` | WPBakery | ✅ | — | ✅ |
| `etch` / `mosaic` | Etch, Mosaic | ✅ | — | ✅ |
| `woocommerce` | Store / products / orders | ✅ | — | ✅ |
| `acf` | Advanced Custom Fields | ✅ | — | ✅ |
| `jetengine` | JetEngine | ✅ | — | ✅ |
| `meta-box` / `pods` / `acpt` / `ase` | Field / CPT plugins | ✅ | — | ✅ |
| `generatepress` / `kadence` | Theme options | ✅ | — | ✅ |
| `code-snippets` | Code Snippets plugin | ✅ | — | ✅ |
| `yoast` | Yoast SEO | ✅ skill + **3 MCP tools** | — | ✅ |
| `smush` | Smush image optimization | ✅ skill + **3 MCP tools** | — | ✅ |
| `vaultshift` | VaultShift security | ✅ skill + **3 MCP tools** | — | ✅ |
| `blogibot` | BlogiBot content | ✅ skill + **3 MCP tools** | — | ✅ |

### How execution differs

| Approach | LayrShift | Novamira Pro |
|----------|-----------|--------------|
| **Elementor / Yoast / etc.** | Typed MCP abilities **when the plugin is active**, plus matching skill playbook | Dedicated native MCP abilities per product |
| **Other builders (Divi, Bricks, …)** | Deep **skill playbooks** + `execute-php` / filesystem / Gutenberg queue | Native MCP abilities per specialization |
| **Custom site logic** | Sandbox PHP, `execute-php`, `read-file` / `write-file` | Same core pattern |

LayrShift optimizes for **breadth**: many integrations and playbooks in one free package. Novamira Pro optimizes for **depth**: first-class APIs per licensed specialization and product memory.

### Distribution & licensing

| | LayrShift | Novamira |
|---|-----------|----------|
| **Get it** | [Download zip](https://wontonee-micro-services.s3.us-east-1.amazonaws.com/layrshift/layrshift-1.0.6.zip) (`layrshift-x.y.z.zip`) | Purchase / license at novamira.ai |
| **Updates** | New zip per version on S3; changelog on [GitHub](https://github.com/wontonee/layrshift) | License server |
| **Use in client projects** | GPL — standard WordPress plugin freedoms | AGPL core; check Pro terms |
| **Why not WordPress.org?** | Filesystem + code execution = too powerful for directory defaults | Same |

### When to choose which

**Choose LayrShift if you want:**

- A **free, GPL** MCP agent stack on GitHub with no license server
- **More abilities included immediately** (integrations + 25 skills)
- Wontonee / LayrShift ecosystem plugins (VaultShift, BlogiBot) with first-class MCP tools
- The same Gutenberg queue and filesystem model you may already know from Novamira

**Choose Novamira if you want:**

- **Novamira Pro** native abilities and commercial support from [Ovation](https://novamira.ai)
- **Project memory** across agent sessions
- **Novamira Visual** browser workspace
- AGPL-aligned workflow with their licensed specialization packs

Both require HTTPS, Application Passwords, administrator opt-in, and **must not run on production**.

---

## What LayrShift does

LayrShift exposes a WordPress REST MCP endpoint at:

```text
https://your-site.example/wp-json/layrshift/v1/mcp
```

Authenticated AI clients can call **abilities** (tools) registered through WordPress’s Abilities API. Typical workflows:

- Inspect and patch theme/plugin files
- Deploy experimental PHP in a sandbox directory (`wp-content/layrshift-sandbox/`)
- Queue Gutenberg block changes and finalize them in the browser
- Read/write Elementor documents when Elementor is active
- Upload zip assets via time-limited signed URLs
- Load Markdown **skill** playbooks for WooCommerce, ACF, Elementor, Divi, and more

The admin UI includes an **MCP connection wizard**, **Abilities Hub** (per-tool toggles), **Activity Log**, and **Agent Sandbox** manager.

---

## Requirements

| Requirement | Notes |
|-------------|--------|
| **WordPress** | 6.9 or newer (Abilities API) |
| **PHP** | 8.0 or newer |
| **HTTPS** | Strongly recommended; enforced by default for ability checks |
| **Administrator** | `manage_options` + WordPress Application Password |

Optional:

- **Elementor**, **Yoast SEO**, **Smush**, **VaultShift**, **BlogiBot** — extra abilities when those plugins are active
- **`wp` CLI** on the server — only in specialized builds; not in the standard release zip
- **Node.js** — for `@automattic/mcp-wordpress-remote` in your MCP client (recommended transport)

---

## Installation

1. **Download** the release zip — latest: **[layrshift-1.0.6.zip](https://wontonee-micro-services.s3.us-east-1.amazonaws.com/layrshift/layrshift-1.0.6.zip)**  
   Other versions: `https://wontonee-micro-services.s3.us-east-1.amazonaws.com/layrshift/layrshift-x.y.z.zip`
2. In WordPress admin: **Plugins → Add New → Upload Plugin**, choose the zip, and activate.
3. Dependencies are already bundled — you do not need Composer or the command line.

### Verify installation

After activation you should see **LayrShift** in the admin menu. If you see *“Run composer install in the plugin directory”*, the `vendor/` folder is missing — delete the plugin and install again from the [official release zip](https://wontonee-micro-services.s3.us-east-1.amazonaws.com/layrshift/layrshift-1.0.6.zip).

---

## Quick start

1. **LayrShift → Configuration → Settings**
   - Read the dev/staging warning and check **I understand this is for dev/staging only**
   - Enable **AI Abilities**
   - Optionally restrict **Allowed administrators** and tune **Execution time limit** (for execute-php)
   - Save

2. **LayrShift → Configuration → MCP**
   - Click **Create application password** (save the password — it is shown once)
   - Copy the ready-made MCP config for your client (Cursor, Claude Code, VS Code, Windsurf, etc.)

3. **In your MCP client**
   - Paste the config or set `WP_API_URL` to your MCP endpoint
   - Restart the client if required
   - Ask the agent to call a simple tool, e.g. `layrshift/read-file` with `path: wp-config.php` (on a **dev** site only)

4. **Abilities Hub** (`LayrShift → Abilities Hub`)
   - Enable or disable individual tools per workflow

---

## Connect your MCP client

LayrShift uses HTTP transport with **WordPress Application Password** authentication (Basic auth over HTTPS). The admin MCP tab generates client-specific JSON for:

- **Cursor**
- **Claude Code / Claude Desktop**
- **VS Code** (Copilot / MCP extensions)
- **Windsurf**
- Other clients compatible with [`@automattic/mcp-wordpress-remote`](https://www.npmjs.com/package/@automattic/mcp-wordpress-remote)

### Endpoint

```text
WP_API_URL=https://your-site.example/wp-json/layrshift/v1/mcp
```

Use the **exact** URL from **LayrShift → MCP** (includes your site’s domain and path).

### Example (conceptual)

Your admin UI provides a complete `mcp.json` snippet. A minimal shape:

```json
{
  "mcpServers": {
    "layrshift": {
      "command": "npx",
      "args": ["-y", "@automattic/mcp-wordpress-remote"],
      "env": {
        "WP_API_URL": "https://your-site.example/wp-json/layrshift/v1/mcp",
        "WP_API_USERNAME": "your-wp-username",
        "WP_API_PASSWORD": "xxxx xxxx xxxx xxxx xxxx xxxx"
      }
    }
  }
}
```

Replace username, password, and URL with values from the MCP tab. **Never commit application passwords to git.**

### Self-signed HTTPS (local `.test` / `.local`)

Local dev URLs often use self-signed certificates. The MCP tab detects this and documents client-specific flags (e.g. allowing insecure TLS for local use only).

---

## Admin interface

| Screen | Path | Purpose |
|--------|------|---------|
| **Configuration** | LayrShift → Configuration | Settings + MCP tabs |
| **Abilities Hub** | LayrShift → Abilities Hub | Enable/disable each MCP tool |
| **Agent Sandbox** | LayrShift → Agent Sandbox | List, enable, disable, delete sandbox PHP files |
| **Activity Log** | LayrShift → Activity Log | Ability invocation audit trail |
| **Block Editor Queue** | LayrShift → Block Editor Queue | Finalize queued Gutenberg changes in the browser |

Admin bar: **LayrShift ON/OFF** quick toggle when abilities are enabled.

---

## Abilities reference

Abilities appear in MCP as tools with the `layrshift/` prefix (and integration-specific names below).

### Core filesystem

| Ability | Description |
|---------|-------------|
| `layrshift/read-file` | Read a file (optional line range) |
| `layrshift/write-file` | Write a file; **`.php` only inside sandbox** |
| `layrshift/edit-file` | Search/replace with automatic backup |
| `layrshift/delete-file` | Delete file or directory (`recursive` optional) |
| `layrshift/list-directory` | List directory entries |
| `layrshift/create-upload-link` | Create a signed, time-limited upload URL |
| `layrshift/disable-file` | Disable a sandbox PHP file (rename to `.disabled`) |
| `layrshift/enable-file` | Re-enable a disabled sandbox file |
| `layrshift/create-admin-access-link` | One-time admin login link for the agent |

### Code execution & CLI (not in standard releases)

These abilities are **not included** in the standard release zip (safer default). You still have the full filesystem, content, and integration toolset below.

| Ability | Description |
|---------|-------------|
| `layrshift/execute-php` | Run PHP in WordPress context (time-limited) |
| `layrshift/run-wp-cli` | Run `wp` CLI commands |
| `layrshift/get-wp-cli-job` | Poll async WP-CLI job output |

### Gutenberg (Block Editor Queue)

| Ability | Description |
|---------|-------------|
| `layrshift/gutenberg-get-content` | Read block content for a post |
| `layrshift/gutenberg-write-content` | Write dynamic `layrshift/*` blocks directly |
| `layrshift/gutenberg-add-pending-change` | Queue native/static block edits |
| `layrshift/gutenberg-list-pending-batches` | List open batches |
| `layrshift/gutenberg-get-pending-batch` | Batch detail |
| `layrshift/gutenberg-delete-pending-change` | Remove queued item |
| `layrshift/gutenberg-delete-pending-batch` | Cancel a batch |
| `layrshift/gutenberg-enable-batch-finalization` | Start browser finalization |
| `layrshift/gutenberg-get-finalizer-runtime` | Check if queue page is online |
| `layrshift/gutenberg-get-finalization-url` | Admin URL for the queue UI |

**Important:** Native Gutenberg blocks must be finalized with the **Block Editor Queue** admin page open in a browser.

### Skills API

| Ability | Description |
|---------|-------------|
| `layrshift/skill-get` | Load a built-in or custom skill playbook |
| `layrshift/skill-write` | Create/update custom skills |
| `layrshift/skill-edit` | Patch a skill file |
| `layrshift/skill-delete` | Remove a custom skill |

Use `layrshift/discover-abilities` (via MCP) to list everything currently registered on your site.

---

## Plugin integrations

When the plugin below is **active**, LayrShift registers additional abilities (also listed in Abilities Hub):

| Plugin | Abilities (prefix) |
|--------|-------------------|
| **Elementor** | `layrshift/elementor-get-document`, `elementor-save-document`, `elementor-list-templates` |
| **Yoast SEO** | `layrshift/yoast-get-post-seo`, `yoast-update-post-seo`, `yoast-get-site-settings` |
| **Smush** | `layrshift/smush-get-stats`, `smush-list-unsmushed`, `smush-run-bulk-smush` |
| **VaultShift** | `layrshift/vaultshift-get-status`, `vaultshift-trigger-scan`, `vaultshift-list-activity` |
| **BlogiBot** | `layrshift/blogibot-get-status`, `blogibot-list-posts`, `blogibot-get-settings` |

Each integration ships with a matching **skill** playbook the agent can load via `layrshift/skill-get`.

---

## Agent skills

Built-in Markdown playbooks live in `includes/skills/built-in/`. They teach agents how to work with WordPress, Gutenberg, page builders, and field plugins without hard-coding every workflow in the model.

**Core:** `wordpress-dev`, `gutenberg-edit-content`, `skill-creator`, `template-studio`

**Page builders (deep playbooks):** `elementor`, `bricks`, `divi`, `breakdance`, `wpbakery`, `etch`, `mosaic`

**Themes:** `generatepress`, `kadence`

**Commerce & fields:** `woocommerce`, `acf`, `jetengine`, `meta-box`, `pods`, `acpt`, `ase`

**Code:** `code-snippets`

**Integrations:** `yoast`, `smush`, `vaultshift`, `blogibot`

Example: `layrshift/skill-get` with `{ "slug": "elementor" }` before editing Elementor templates.

---

## Security model

LayrShift is **secure by opt-in**, not secure by obscurity. Understand this before enabling abilities.

### Defaults

- Abilities **off** until an admin acknowledges risk and enables them
- Permission checks require **`manage_options`** (configurable user allowlist)
- **HTTPS enforcement** on ability permission checks (can be disabled for local dev)
- **Application Password** required for MCP; passwords can be revoked per device
- **Activity log** records ability name, user, IP, duration, and redacted input/output

### Path safety

- All filesystem paths resolved under **`ABSPATH`**
- `..` and traversal patterns rejected
- PHP **writes** restricted to **`wp-content/layrshift-sandbox/`** (unless reading existing PHP elsewhere)
- Upload tokens sent only via **`X-LayrShift-Upload-Token`** header; executable uploads outside sandbox blocked
- Optional **restrict core deletion** prevents deleting WordPress core files

### Sandbox

- Experimental PHP lives in `wp-content/layrshift-sandbox/`
- Manifest + `.htaccess` deny direct web access
- Per-file enable/disable without deleting code
- **Safe mode** if autoload breaks the site (see below)

### What this does *not* protect against

A compromised MCP client, leaked application password, or malicious admin can still damage a dev site. Treat application passwords like root SSH keys. Use separate staging sites, short-lived passwords, and disable abilities when not in use.

---

## Troubleshooting

### “Application passwords are not available”

**Wordfence** disables them by default:

1. **Wordfence → All Options → Brute Force Protection** — uncheck *Disable WordPress application passwords*, save, reload LayrShift MCP tab.

**Or** in **LayrShift → Settings**, enable **Allow Application Passwords** (requires AI Abilities on; administrators only). LayrShift can override some security-plugin blocks when you explicitly opt in.

Similar plugins (Solid Security, All In One WP Security) may need the same treatment.

### MCP client cannot connect

- Confirm **AI Abilities** are enabled in Settings
- Confirm URL is exactly `…/wp-json/layrshift/v1/mcp`
- Use HTTPS or disable HTTPS enforcement for local `.test` sites
- Regenerate application password; revoke old ones
- Check **Activity Log** for denied requests

### Gutenberg changes not appearing

- Native blocks need **Block Editor Queue** open: `wp-admin/admin.php?page=layrshift-gutenberg-finalize`
- Call `layrshift/gutenberg-enable-batch-finalization` after queueing
- Check `layrshift/gutenberg-get-finalizer-runtime` for `online: true`

### CSS/JS missing after install

Use the official **release zip** from [layrshift-1.0.6.zip](https://wontonee-micro-services.s3.us-east-1.amazonaws.com/layrshift/layrshift-1.0.6.zip). Do not zip the plugin folder yourself — that often drops required CSS/JS files.

### Composer / vendor errors

If you installed from a release zip and still see a Composer message, the download may be incomplete. Delete the plugin folder and upload a fresh copy from the official download link.

---

## Safe mode & crash recovery

If sandbox autoload breaks wp-admin:

1. Visit any admin URL with: `?layrshift-safe-mode=1`
2. Open **LayrShift → Agent Sandbox** and disable or delete the offending file
3. Clear safe mode from the Sandbox screen

LayrShift also ships crash-recovery hooks to reduce white-screen risk during agent experiments.

---

## Changelog

See [readme.txt](readme.txt) for the full WordPress-style changelog, or the [GitHub repository](https://github.com/wontonee/layrshift) for source and issues.

**1.0.6** — Plugin Check / PHPCS compliance, admin escaping, GitHub distribution polish, Plugin Check ignore filters for local scans, release zip output moved to `../dist/`.

---

## Support & contributing

- **Download:** [layrshift-1.0.6.zip](https://wontonee-micro-services.s3.us-east-1.amazonaws.com/layrshift/layrshift-1.0.6.zip)
- **Issues:** [github.com/wontonee/layrshift/issues](https://github.com/wontonee/layrshift/issues)
- **Source:** [github.com/wontonee/layrshift](https://github.com/wontonee/layrshift)
- **Support:** [dev@wontonee.com](mailto:dev@wontonee.com)
- **Company:** [Wontonee DigitalCraft LLP](https://wontonee.com)

Pull requests welcome. Contributors working from source: clone the repo and run `composer install` in the plugin directory. Please do not post application passwords or production site URLs in issues.

---

## License

LayrShift is free software: you can redistribute it and/or modify it under the terms of the **GNU General Public License** as published by the Free Software Foundation, either version 2 of the License, or any later version.

LayrShift is distributed in the hope that it will be useful, but **WITHOUT ANY WARRANTY**; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See [GPL-2.0](https://www.gnu.org/licenses/gpl-2.0.html).

Bundled [wordpress/mcp-adapter](https://github.com/WordPress/mcp-adapter) and other Composer dependencies are subject to their respective licenses.

---

<p align="center">Made with ❤️ by <a href="https://wontonee.com">Wontonee</a> · <a href="mailto:dev@wontonee.com">dev@wontonee.com</a></p>
