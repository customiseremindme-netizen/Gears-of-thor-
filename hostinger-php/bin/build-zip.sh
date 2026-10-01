#!/usr/bin/env bash
# Builds dist/got-fitnezz-website.zip: everything needed on the server, ready
# to extract straight into Hostinger's public_html (no tests or Git history).
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
OUT="$ROOT/dist/got-fitnezz-website.zip"
STAGE="$(mktemp -d)"
trap 'rm -rf "$STAGE"' EXIT

cp -r "$ROOT/.htaccess" "$ROOT/app" "$ROOT/bin" "$ROOT/docs" "$ROOT/public" "$ROOT/storage" "$ROOT/README.md" "$STAGE/"
rm -f "$STAGE/bin/build-zip.sh"
# Never ship local data, secrets or uploads.
rm -f "$STAGE/storage/config.php" "$STAGE/storage/setup-code.txt" "$STAGE/storage/recovery.txt"
find "$STAGE/storage" -mindepth 2 -type f -not -name '.gitkeep' -delete
find "$STAGE/public/uploads" -mindepth 1 -not -name '.htaccess' -not -name 'index.html' -exec rm -rf {} + 2>/dev/null || true

mkdir -p "$ROOT/dist"
rm -f "$OUT"
(cd "$STAGE" && zip -qr -X "$OUT" . -x '*.DS_Store')
echo "Created $OUT ($(du -h "$OUT" | cut -f1))"
