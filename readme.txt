=== LAYRSHIFT ===
Contributors: wontonee
Tags: mcp, ai, agent, development, cursor, claude
Requires at least: 6.9
Tested up to: 6.9
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

Built on the official [WordPress MCP Adapter](https://github.com/WordPress/mcp-adapter) and Abilities API (WordPress 6.9+).

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/layrshift/` or install from GitHub.
2. Run `composer install --no-dev` inside the plugin directory.
3. Activate LAYRSHIFT in WordPress admin.
4. Go to **Settings → LAYRSHIFT**, acknowledge the dev/staging warning, and enable AI Abilities.
5. Open **LayrShift → Connect** and follow the setup wizard.

== Frequently Asked Questions ==

= Is this safe for production? =

No. LAYRSHIFT is designed for dev/staging environments only.

= Which AI clients are supported? =

Any MCP-compatible client that supports HTTP transport with custom headers.

== Changelog ==

= 1.0.0 =
* Initial release with 9 core abilities, sandbox, admin UI, and MCP integration.
