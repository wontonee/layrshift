=== LayrShift ===
Contributors: wontonee
Tags: mcp, ai, agent, development, cursor
Requires at least: 6.9
Tested up to: 7.0
Requires PHP: 8.0
Stable tag: 1.0.8
License: AGPL-3.0-or-later
License URI: https://www.gnu.org/licenses/agpl-3.0.html

Forge WordPress with your AI agent. MCP server for dev/staging sites.

== Description ==

<p align="center"><img src="assets/banner-1544x500.png" alt="LayrShift" width="100%" /></p>

LayrShift exposes a secure MCP (Model Context Protocol) server inside WordPress so AI clients like Cursor, Claude Code, and VS Code Copilot can:

* Read, write, edit, and delete files under the site root (PHP writes are sandbox-only)
* Browse directories
* Upload plugins, themes, and media via signed URLs
* Deploy persistent sandbox PHP with crash recovery (safe mode)
* Use Gutenberg, Elementor, and other integrations when those plugins are active

**For development and staging sites only.** Requires HTTPS (configurable), WordPress Application Passwords, and administrator access.

Built on the official [WordPress MCP Adapter](https://github.com/WordPress/mcp-adapter) and Abilities API (WordPress 6.9+). Bundled Composer dependencies are included in the plugin package.

Download: https://wontonee-micro-services.s3.us-east-1.amazonaws.com/layrshift/layrshift-1.0.8.zip. Source on [GitHub](https://github.com/wontonee/layrshift) (AGPL-3.0-or-later, same model as [Novamira](https://github.com/use-novamira/novamira)). Clone the repository and run `composer install` for development.

== Security ==

LayrShift is intentionally powerful for dev/staging administrators:

* Filesystem abilities can read, write, and delete files under the site root (PHP writes are sandbox-only).

**AI Abilities are disabled by default** until an administrator acknowledges the dev/staging risk and explicitly enables them. Access requires `manage_options` and a WordPress Application Password. Do not use on production sites.

Hardening included in this release:

* Resolved paths must stay inside `ABSPATH`; path traversal is rejected.
* Upload tokens are sent via the `X-LayrShift-Upload-Token` header only; executable uploads outside the sandbox are blocked.
* HTTPS enforcement applies to ability permission checks when enabled.
* Ability invocation logs redact tokens and passwords.

== Installation ==

1. Download [layrshift-1.0.8.zip](https://wontonee-micro-services.s3.us-east-1.amazonaws.com/layrshift/layrshift-1.0.8.zip) or from [GitHub Releases](https://github.com/wontonee/layrshift/releases)
2. In wp-admin: Plugins → Add New → Upload Plugin, install, and activate.
3. Open **LayrShift → Configuration**.
4. On the **Settings** tab, acknowledge the dev/staging warning and enable **AI Abilities**.
5. Open the **MCP** tab, generate an application password, and connect your MCP client (Cursor, Claude Code, etc.).

For full documentation see README.md in the GitHub repository.

== Frequently Asked Questions ==

= Is this safe for production? =

No. LayrShift is designed for dev/staging environments only.

= Which AI clients are supported? =

Any MCP-compatible client that supports HTTP transport with Application Password authentication.

= Wordfence blocks Application Passwords. How do I fix it? =

Wordfence disables Application Passwords by default. Either:

1. **Wordfence → All Options → Brute Force Protection** — uncheck “Disable WordPress application passwords”, save, and reload LayrShift.
2. Or in **LayrShift → Configuration → Settings**, enable **Allow Application Passwords** (requires AI Abilities enabled; administrators only).

= Do I need to run Composer? =

No. The release zip includes bundled dependencies.

= Where do I download LayrShift? =

LayrShift is not on the WordPress.org plugin directory. Download https://wontonee-micro-services.s3.us-east-1.amazonaws.com/layrshift/layrshift-1.0.8.zip (or [GitHub Releases](https://github.com/wontonee/layrshift/releases)) and install via Plugins → Add New → Upload Plugin.

== Screenshots ==

1. MCP connection wizard with application password setup
2. Abilities Hub for enabling and disabling individual MCP tools
3. Activity log of ability invocations

== Changelog ==

= 1.0.8 =
* Tier 1 integrations: WooCommerce, Rank Math SEO, Genesis Framework, Astra, Contact Form 7, Wordfence, and UpdraftPlus — each with 3 MCP abilities and a built-in skill playbook.

= 1.0.7 =
* AGPL-3.0-or-later license, CONTRIBUTING.md, and GitHub Releases distribution (Novamira model).
* README full-bleed banner and branding cleanup; S3 mirror URL updated.
* Restore WordPress admin menu icon (dashicons-layout); LayrShift icon in app header only.
* Remove Wontonee logo from admin UI.

= 1.0.6 =
* WordPress Plugin Check compliance: filesystem helpers, i18n placeholders, admin view escaping, and PHPCS fixes for wp.org submission.

= 1.0.5 =
* Fix fatal error on Linux hosts: correct Composer PSR-4 paths for Elementor, Yoast, Smush, VaultShift, and BlogiBot integration loaders.

= 1.0.4 =
* Wordfence / security-plugin support: detect blocked Application Passwords, show plugin-specific steps, optional LayrShift override for administrators.

= 1.0.3 =
* Fix PHP warnings when execute-php / WP-CLI abilities are omitted from the WordPress.org package (no autoload of missing classes).
* Regenerate Composer autoload in release builds after excluding dev-only ability files.

= 1.0.2 =
* Fix release zip excluding admin CSS/JS (`assets/` distignore pattern matched `admin/assets/`).

= 1.0.1 =
* WordPress.org automated scan compliance: exclude code-execution abilities from the directory package, replace `move_uploaded_file`, omit dotfiles from the release zip, align readme plugin name with the plugin header.

= 1.0.0 =
* Initial WordPress.org release.
* MCP server (HTTP transport) with filesystem and admin access abilities.
* Gutenberg Block Editor Queue abilities and Elementor document read/write abilities.
* Optional integrations: Yoast SEO, Smush, VaultShift, BlogiBot (when those plugins are active).
* Skills library, Abilities Hub, admin bar status chip, and MCP connection wizard.
* Compact MCP tool names for Cursor and other clients with strict name-length limits.

<p align="center"><img src="assets/icon-256x256.png" alt="LayrShift" width="64" /></p>

<p align="center"><a href="https://wontonee.com"><img src="assets/wontonee-logo.png" alt="Wontonee DigitalCraft LLP" height="36" /></a></p>

<p align="center">Made with love by <a href="https://wontonee.com">Wontonee</a> · <a href="mailto:dev@wontonee.com">dev@wontonee.com</a></p>
