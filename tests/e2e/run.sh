#!/usr/bin/env bash
# End-to-end browser test on a fresh, throw-away install.
#   bash tests/e2e/run.sh            (SQLite)
#   DB=mysql DB_NAME=gym DB_USER=gym DB_PASS=secret bash tests/e2e/run.sh
# Needs PHP 8.1+, Node.js and Playwright (npm i -g playwright).
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
WORK="$(mktemp -d)"
PORT="${PORT:-8765}"
EMAIL="owner@example.com"
PASSWORD="E2E-test-password-2026"

cleanup() {
  if [[ -n "${SERVER_PID:-}" ]]; then kill "$SERVER_PID" 2>/dev/null || true; fi
  rm -rf "$WORK"
}
trap cleanup EXIT

cp -r "$ROOT/app" "$ROOT/bin" "$ROOT/public" "$ROOT/docs" "$WORK/"
find "$WORK/public/uploads" -mindepth 1 -not -name '.htaccess' -not -name 'index.html' -exec rm -rf {} + 2>/dev/null || true
mkdir -p "$WORK/storage/database" "$WORK/storage/logs" "$WORK/storage/cache" "$WORK/storage/sessions"

if [[ "${DB:-sqlite}" == "mysql" ]]; then
  php "$WORK/bin/install.php" --db=mysql --db-host="${DB_HOST:-127.0.0.1}" --db-name="$DB_NAME" --db-user="$DB_USER" --db-pass="${DB_PASS:-}" \
    --email="$EMAIL" --password="$PASSWORD" --name="Test Owner" --url="http://127.0.0.1:$PORT" --force
else
  php "$WORK/bin/install.php" --db=sqlite --email="$EMAIL" --password="$PASSWORD" --name="Test Owner" --url="http://127.0.0.1:$PORT"
fi

php -S "127.0.0.1:$PORT" -t "$WORK/public" "$WORK/public/index.php" > "$WORK/server.log" 2>&1 &
SERVER_PID=$!
sleep 1

export NODE_PATH="${NODE_PATH:-$(npm root -g)}"
BASE_URL="http://127.0.0.1:$PORT" ADMIN_EMAIL="$EMAIL" ADMIN_PASSWORD="$PASSWORD" \
  FIXTURES="$ROOT/tests/e2e/fixtures" SCREENSHOTS="${SCREENSHOTS:-}" WORK_DIR="$WORK" \
  node "$ROOT/tests/e2e/e2e.js"

if [[ -s "$WORK/storage/logs/app-$(date -u +%Y-%m).log" ]]; then
  echo "--- application log ---"
  cat "$WORK/storage/logs/app-$(date -u +%Y-%m).log"
fi
