#!/usr/bin/env bash
# Prepare the isolated `testing` database for PHPUnit (never touch mis_dev / production).
# Schema lives in database/migrations/install and database/migrations/update/0_1.
# `artisan migrate` does not see those paths.
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$ROOT_DIR"

DB_CONNECTION="${DB_CONNECTION:-pgsql}"
DB_HOST="${DB_HOST:-127.0.0.1}"
DB_PORT="${DB_PORT:-5432}"
DB_USERNAME="${DB_USERNAME:?DB_USERNAME must be provided}"
DB_PASSWORD="${DB_PASSWORD:?DB_PASSWORD must be provided}"
DB_DATABASE="${DB_DATABASE:-testing}"
ARTISAN="${ARTISAN:-php artisan}"

export APP_ENV=testing
export DB_CONNECTION DB_HOST DB_PORT DB_USERNAME DB_PASSWORD DB_DATABASE
# The ICD-10 install migration calls eHealth. CI has no PSK; keep the table empty.
export EHEALTH_SEED_ICD10=false

if [[ "${DB_DATABASE}" != "testing" ]]; then
  echo "Refusing to migrate database '${DB_DATABASE}'. This script only prepares 'testing'." >&2
  exit 1
fi

echo "==> Clearing config cache (prevents mis_dev baked into bootstrap/cache/config.php)"
$ARTISAN config:clear --no-interaction

if command -v psql >/dev/null 2>&1; then
  echo "==> Ensuring database '${DB_DATABASE}' exists"
  PGPASSWORD="${DB_PASSWORD}" psql -h "${DB_HOST}" -p "${DB_PORT}" -U "${DB_USERNAME}" -d postgres \
    -tc "SELECT 1 FROM pg_database WHERE datname='${DB_DATABASE}'" | grep -q 1 \
    || PGPASSWORD="${DB_PASSWORD}" psql -h "${DB_HOST}" -p "${DB_PORT}" -U "${DB_USERNAME}" -d postgres \
      -c "CREATE DATABASE ${DB_DATABASE} OWNER ${DB_USERNAME};"
fi

echo "==> Installing schema on '${DB_DATABASE}' (database/migrations/install)"
# `install` reads the migrations table before running files. A fresh database does not have it yet.
$ARTISAN migrate:install --no-interaction
# --no-interaction is required: `install` shows help and exits when called with no options.
$ARTISAN install --no-interaction

echo "==> Applying eHealth update migrations (database/migrations/update/0_1)"
$ARTISAN update --no-interaction

echo "==> Testing DB ready: ${DB_DATABASE}"
