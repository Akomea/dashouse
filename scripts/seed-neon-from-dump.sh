#!/usr/bin/env bash
# Seed Neon from a Supabase dump produced by dump-supabase.sh.
# Run with DATABASE_URL set in your shell environment.
# 1) Ensure Neon has schema: npm run db:schema  (requires DATABASE_URL in .env)
# 2) Then run this script to load data: ./scripts/seed-neon-from-dump.sh
# Prefers supabase-data-only.sql if present; otherwise uses supabase-public.dump.

set -euo pipefail
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
cd "$REPO_ROOT"

if [[ -z "$DATABASE_URL" ]]; then
  echo "DATABASE_URL is required." >&2
  exit 1
fi

if [[ -f supabase-data-only.sql ]]; then
  echo "Loading data from supabase-data-only.sql into Neon ..."
  PSQL_BIN="${PSQL_BIN:-}"
  if [[ -z "$PSQL_BIN" ]]; then
    if [[ -x "/opt/homebrew/opt/postgresql@17/bin/psql" ]]; then
      PSQL_BIN="/opt/homebrew/opt/postgresql@17/bin/psql"
    elif command -v psql >/dev/null 2>&1; then
      PSQL_BIN="$(command -v psql)"
    else
      echo "psql not found." >&2
      exit 1
    fi
  fi

  "$PSQL_BIN" "$DATABASE_URL" -v ON_ERROR_STOP=1 -c "TRUNCATE TABLE business_info, categories, menu_items, gift_shop_items, photos, settings RESTART IDENTITY CASCADE;"
  "$PSQL_BIN" "$DATABASE_URL" -v ON_ERROR_STOP=1 -f supabase-data-only.sql
  echo "Done."
elif [[ -f supabase-public.dump ]]; then
  echo "Restoring supabase-public.dump into Neon (schema + data) ..."
  if [[ -x "/opt/homebrew/opt/postgresql@17/bin/pg_restore" ]]; then
    /opt/homebrew/opt/postgresql@17/bin/pg_restore -d "$DATABASE_URL" --no-owner --no-privileges supabase-public.dump
  elif command -v pg_restore >/dev/null 2>&1 && [[ "$(pg_restore --version | awk '{print $3}' | cut -d. -f1)" -ge 17 ]]; then
    pg_restore -d "$DATABASE_URL" --no-owner --no-privileges supabase-public.dump
  else
    docker run --rm -i postgres:17 pg_restore -d "$DATABASE_URL" --no-owner --no-privileges < supabase-public.dump
  fi
  echo "Done."
else
  echo "No dump file found. Run ./scripts/dump-supabase.sh first (requires SUPABASE_DB_PASSWORD or SUPABASE_URL)." >&2
  exit 1
fi
