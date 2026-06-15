#!/usr/bin/env bash
# Build layrshift-1.0.0.zip respecting .distignore (no wp-cli required).
set -euo pipefail

PLUGIN_DIR="$(cd "$(dirname "$0")/.." && pwd)"
DIST_DIR="$(cd "$PLUGIN_DIR/.." && pwd)/dist"
VERSION="$(grep -m1 '^Stable tag:' "$PLUGIN_DIR/readme.txt" | awk '{print $3}')"
STAGING="/tmp/layrshift-zip-build-$$"
ZIP_OUT="$DIST_DIR/layrshift-${VERSION}.zip"

cd "$PLUGIN_DIR"
composer install --no-dev --optimize-autoloader --quiet

rm -rf "$STAGING"
mkdir -p "$STAGING/layrshift" "$DIST_DIR"

rsync -a \
  --exclude='tests/' \
  --exclude='phpunit.xml.dist' \
  --exclude='.phpunit.result.cache' \
  --exclude='.cursor/' \
  --exclude='.git/' \
  --exclude='.gitignore' \
  --exclude='.gitattributes' \
  --exclude='.distignore' \
  --exclude='node_modules/' \
  --exclude='create-*-landing-pages.php' \
  --exclude='composer.lock' \
  --exclude='admin/views/tabs/generate.php' \
  --exclude='admin/views/tabs/preview.php' \
  --exclude='admin/assets/template-studio.js' \
  --exclude='tests/mcp-capability-matrix-results.json' \
  --exclude='docs/MCP-TEST-REPORT.md' \
  --exclude='docs/plans/' \
  --exclude='README.md' \
  --exclude='/assets/' \
  --exclude='abilities/ExecutePhp.php' \
  --exclude='abilities/RunWpCli.php' \
  --exclude='scripts/' \
  --exclude='layrshift-*.zip' \
  --exclude='.DS_Store' \
  ./ "$STAGING/layrshift/"

test -f "$STAGING/layrshift/vendor/autoload.php"
test ! -d "$STAGING/layrshift/.git"
test ! -f "$STAGING/layrshift/vendor/bin/phpunit"
test ! -f "$STAGING/layrshift/.gitignore"
test ! -f "$STAGING/layrshift/abilities/ExecutePhp.php"
test ! -f "$STAGING/layrshift/abilities/RunWpCli.php"
test -f "$STAGING/layrshift/admin/assets/admin.css"
test -f "$STAGING/layrshift/admin/assets/admin.js"
test -f "$STAGING/layrshift/admin/assets/abilities-hub.css"

( cd "$STAGING/layrshift" && composer dump-autoload --no-dev --optimize --quiet )

! rg -q 'ExecutePhp|RunWpCli' "$STAGING/layrshift/vendor/composer/autoload_classmap.php"

rm -f "$ZIP_OUT"
cd "$STAGING"
zip -rq "$ZIP_OUT" layrshift
rm -rf "$STAGING"

echo "Built $ZIP_OUT ($(du -h "$ZIP_OUT" | awk '{print $1}'))"
