# LayrShift

Forge WordPress with your AI agent — MCP server plugin for dev/staging sites.

## Requirements

- WordPress 6.9+
- PHP 8.0+
- HTTPS (recommended, configurable)
- Composer

## Setup

```bash
cd wp-content/plugins/LAYRSHIFT
composer install --no-dev
```

Activate in WordPress admin, then:

1. **Settings → LAYRSHIFT** — acknowledge risk and enable AI Abilities
2. **LayrShift → Connect** — copy MCP config into Cursor or Claude Code
3. Create a WordPress Application Password for authentication

## MCP connection

Use `@automattic/mcp-wordpress-remote` via npx (same transport as Novamira). Copy the
ready-made config from **LayrShift → MCP** — tabs for Cursor, Claude Code, VS Code,
Windsurf, and other clients.

```
WP_API_URL=https://yoursite.example/wp-json/layrshift/v1/mcp
```

## Abilities

| Ability | Description |
|---------|-------------|
| `layrshift/execute-php` | Run PHP in WordPress context |
| `layrshift/read-file` | Read filesystem files |
| `layrshift/write-file` | Write files (PHP → sandbox only) |
| `layrshift/edit-file` | Exact string replace with backup |
| `layrshift/delete-file` | Delete files/directories |
| `layrshift/disable-file` | Disable sandbox PHP file |
| `layrshift/enable-file` | Re-enable sandbox PHP file |
| `layrshift/list-directory` | Browse directories |
| `layrshift/create-upload-link` | Signed upload URL |

## Skills

Built-in agent playbooks ship in `includes/skills/built-in/` — load via `layrshift/skill-get`.
Covers Gutenberg, Elementor, Bricks, WooCommerce, ACF, and 14 more specializations
(see [docs/SKILLS-CATALOG.md](docs/SKILLS-CATALOG.md)).

## Safe mode

If a sandbox file breaks the site, visit any admin URL with `?layrshift-safe-mode=1`.

## Development

```bash
composer install
vendor/bin/phpunit
```

## License

GPL-2.0-or-later
