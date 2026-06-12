=== LAYRSHIFT ===
Contributors: wontonee
Tags: mcp, ai, agent, development, cursor
Requires at least: 6.9
Tested up to: 7.0
Requires PHP: 8.0
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Forge WordPress with your AI agent. MCP server for dev/staging sites.

== Description ==

LAYRSHIFT exposes a secure MCP (Model Context Protocol) server inside WordPress so AI clients like Cursor, Claude Code, and VS Code Copilot can:

* Execute PHP in the full WordPress environment
* Read, write, edit, and delete files
* Browse directories
* Upload plugins, themes, and media via signed URLs
* Deploy persistent sandbox PHP with crash recovery (safe mode)

**For development and staging sites only.** Requires HTTPS (configurable), WordPress Application Passwords, and administrator access.

Built on the official [WordPress MCP Adapter](https://github.com/WordPress/mcp-adapter) and Abilities API (WordPress 6.9+). Bundled Composer dependencies are included in the plugin package.

== Security ==

LAYRSHIFT is intentionally powerful for dev/staging administrators:

* `layrshift/execute-php` runs arbitrary PHP in the WordPress environment.
* `layrshift/run-wp-cli` executes WP-CLI commands on the server.
* Filesystem abilities can read, write, and delete files under the site root (PHP writes are sandbox-only).

**AI Abilities are disabled by default** until an administrator acknowledges the dev/staging risk and explicitly enables them. Access requires `manage_options` and a WordPress Application Password. Do not use on production sites.

Hardening included in this release:

* Resolved paths must stay inside `ABSPATH`; path traversal is rejected.
* Upload tokens are sent via the `X-LayrShift-Upload-Token` header only; executable uploads outside the sandbox are blocked.
* HTTPS enforcement applies to ability permission checks when enabled.
* Ability invocation logs redact tokens, passwords, and PHP execution payloads.

== Installation ==

1. Install and activate LayrShift from the WordPress plugin directory (or upload the plugin zip).
2. In wp-admin, open **LayrShift → Configuration**.
3. On the **Settings** tab, acknowledge the dev/staging warning and enable **AI Abilities**.
4. Open the **MCP** tab, generate an application password, and connect your MCP client (Cursor, Claude Code, etc.).

== Frequently Asked Questions ==

= Is this safe for production? =

No. LAYRSHIFT is designed for dev/staging environments only.

= Which AI clients are supported? =

Any MCP-compatible client that supports HTTP transport with Application Password authentication.

= Do I need to run Composer? =

No. The WordPress.org package includes bundled dependencies.

== Screenshots ==

1. MCP connection wizard with application password setup
2. Abilities Hub for enabling and disabling individual MCP tools
3. Activity log of ability invocations

== Changelog ==

= 1.0.0 =
* Initial WordPress.org release.
* MCP server (HTTP transport) with filesystem, PHP execution, WP-CLI, and admin access abilities.
* Gutenberg Block Editor Queue abilities and Elementor document read/write abilities.
* Optional integrations: Yoast SEO, Smush, VaultShift, BlogiBot (when those plugins are active).
* Skills library, Abilities Hub, admin bar status chip, and MCP connection wizard.
* Compact MCP tool names for Cursor and other clients with strict name-length limits.
