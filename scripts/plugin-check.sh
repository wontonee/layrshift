#!/usr/bin/env bash
# Run WordPress Plugin Check PHPCS rules against the LayrShift release package.
set -euo pipefail

PLUGIN_DIR="$(cd "$(dirname "$0")/.." && pwd)"
DIST_DIR="$(cd "$PLUGIN_DIR/.." && pwd)/dist"
PCP_PLUGIN="${PLUGIN_CHECK_DIR:-$PLUGIN_DIR/../plugin-check}"
PCP_BIN="$PCP_PLUGIN/vendor/bin/phpcs"
RULESET="$PCP_PLUGIN/phpcs-rulesets/plugin-review.xml"
STAGING="/tmp/layrshift-plugin-check-$$"
VERSION="$(grep -m1 '^Stable tag:' "$PLUGIN_DIR/readme.txt" | awk '{print $3}')"
ZIP_PATH="$DIST_DIR/layrshift-${VERSION}.zip"

if [[ ! -f "$PCP_BIN" ]]; then
	echo "Plugin Check not found at: $PCP_PLUGIN" >&2
	echo "Install it in wp-content/plugins/plugin-check or set PLUGIN_CHECK_DIR." >&2
	exit 1
fi

chmod +x "$PCP_BIN" 2>/dev/null || true

echo "Building release tree (same exclusions as layrshift-${VERSION}.zip)..."
bash "$PLUGIN_DIR/scripts/build-release-zip.sh" >/dev/null
rm -rf "$STAGING"
mkdir -p "$STAGING"
unzip -q "$ZIP_PATH" -d "$STAGING"

echo ""
echo "Running Plugin Check PHPCS (plugin-review.xml) on layrshift-${VERSION}.zip ..."
echo "Admin UI equivalent: Tools → Plugin Check → select LayrShift → Check it!"
echo ""

set +e
php "$PCP_BIN" --standard="$RULESET" --report=summary --extensions=php,js,css "$STAGING/layrshift"
CODE=$?
set -e

echo ""
if [[ $CODE -eq 0 ]]; then
	echo "PASS: No PHPCS errors in the release package."
else
	echo "FAIL: PHPCS reported issues (exit $CODE). Run with REPORT=full for details:"
	echo "  REPORT=full bash scripts/plugin-check.sh"
fi

rm -rf "$STAGING"
exit "$CODE"
