#!/usr/bin/env bash
# Dump public schema (selected tables) from Supabase Postgres.
# Run with SUPABASE_URL set, or SUPABASE_DB_PASSWORD (builds URL for db.lvatvujwtyqwdsbqxjvm.supabase.co).
# Output: supabase-public.dump (custom format) and supabase-data-only.sql (data-only plain SQL).
# Uses local pg_dump when version is compatible; otherwise falls back to docker postgres:17.

set -euo pipefail
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
cd "$REPO_ROOT"

SUPABASE_HOST="${SUPABASE_DB_HOST:-db.lvatvujwtyqwdsbqxjvm.supabase.co}"
SUPABASE_USER="${SUPABASE_DB_USER:-postgres}"
SUPABASE_DB="${SUPABASE_DB_NAME:-postgres}"

if [[ -n "${SUPABASE_URL:-}" ]]; then
  CONN="$SUPABASE_URL"
else
  if [[ -z "${SUPABASE_DB_PASSWORD:-}" ]]; then
    echo "Set SUPABASE_URL or SUPABASE_DB_PASSWORD (and optionally SUPABASE_DB_HOST) in env." >&2
    exit 1
  fi
  CONN="postgresql://${SUPABASE_USER}:${SUPABASE_DB_PASSWORD}@${SUPABASE_HOST}:5432/${SUPABASE_DB}?sslmode=require"
fi

TABLE_ARGS=(
  -t categories
  -t menu_items
  -t business_info
  -t gift_shop_items
  -t photos
  -t settings
)

PG_DUMP_BIN="${PG_DUMP_BIN:-}"
if [[ -z "$PG_DUMP_BIN" ]]; then
  if [[ -x "/opt/homebrew/opt/postgresql@17/bin/pg_dump" ]]; then
    PG_DUMP_BIN="/opt/homebrew/opt/postgresql@17/bin/pg_dump"
  elif command -v pg_dump >/dev/null 2>&1; then
    PG_DUMP_BIN="$(command -v pg_dump)"
  else
    PG_DUMP_BIN=""
  fi
fi

PG_DUMP_MAJOR=0
if [[ -n "$PG_DUMP_BIN" ]]; then
  PG_DUMP_MAJOR="$("$PG_DUMP_BIN" --version | awk '{print $3}' | cut -d. -f1)"
fi

run_pg_dump() {
  if [[ "${PG_DUMP_FORCE_DOCKER:-0}" == "1" ]]; then
    docker run --rm postgres:17 pg_dump "$@"
  elif [[ -n "$PG_DUMP_BIN" && "${PG_DUMP_MAJOR:-0}" -ge 17 ]]; then
    "$PG_DUMP_BIN" "$@"
  else
    docker run --rm postgres:17 pg_dump "$@"
  fi
}

echo "Dumping schema + data to supabase-public.dump ..."
run_pg_dump "$CONN" \
  --schema=public \
  --no-owner --no-privileges \
  "${TABLE_ARGS[@]}" \
  -F c > supabase-public.dump

echo "Dumping data-only to supabase-data-only.sql ..."
run_pg_dump "$CONN" \
  --schema=public --data-only \
  --no-owner --no-privileges \
  "${TABLE_ARGS[@]}" \
  -F p > supabase-data-only.sql

echo "Done. Files: supabase-public.dump, supabase-data-only.sql"
