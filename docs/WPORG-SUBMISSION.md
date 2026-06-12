# WordPress.org submission guide (LayrShift 1.0.0)

## Package ready

| Artifact | Path |
|----------|------|
| Release zip (upload this) | `layrshift-1.0.0.zip` (plugin root) |
| SVN assets (banners, icons, screenshots) | `assets/` (not inside the zip) |

## Step 1 — Submit for review

1. Log in at https://wordpress.org/plugins/developers/
2. **Add your plugin** → upload `layrshift-1.0.0.zip`
3. Wait for slug approval email (expected slug: `layrshift`)

## Step 2 — SVN layout (after approval)

```bash
svn co https://plugins.svn.wordpress.org/layrshift
cd layrshift
```

```
layrshift/
  trunk/          # plugin files (contents of release zip)
  tags/1.0.0/     # frozen 1.0.0 tag
  assets/         # banners, icons, screenshots from assets/
```

## Step 3 — Deploy

```bash
# Extract or rsync release zip contents into trunk
unzip -q /path/to/layrshift-1.0.0.zip -d /tmp/layrshift-deploy
rsync -av --delete /tmp/layrshift-deploy/layrshift/ trunk/

# Copy tag
rsync -av trunk/ tags/1.0.0/

# Copy wp.org assets (separate from plugin zip)
cp /path/to/layrshift/assets/*.png assets/

svn add --force trunk tags/1.0.0 assets
svn status
svn ci -m "Initial release 1.0.0"
```

## Step 4 — Verify on wordpress.org

- Plugin page shows banner and icon
- **Stable tag** in trunk `readme.txt` is `1.0.0`
- Install on a test site without running Composer

## Review talking points

- Dev/staging only; abilities off by default until admin acknowledges risk
- `execute-php` and filesystem tools require Application Password + `manage_options`
- Bundled `wordpress/mcp-adapter` in `vendor/` (GPL-compatible)
- HTTPS enforcement and path jail documented in readme Security section

## Rebuild zip

```bash
cd wp-content/plugins/layrshift
composer install --no-dev --optimize-autoloader
# Then run rsync/zip per .distignore (see project scripts or plan)
```

## Deferred (not in 1.0.0)

- MCP Agent Activity Feed — see `docs/plans/MCP-AGENT-ACTIVITY-FEED.md`
