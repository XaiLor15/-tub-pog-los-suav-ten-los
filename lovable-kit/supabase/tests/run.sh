#!/usr/bin/env bash
# Runs the migrations + tests on a throw-away local PostgreSQL database (never production).
#   DB=kit_test bash supabase/tests/run.sh
set -euo pipefail
cd "$(dirname "$0")/.."
DB="${DB:-kit_test}"
psql -q -c "drop database if exists ${DB}" -c "create database ${DB}" >/dev/null
psql -q -v ON_ERROR_STOP=1 -d "${DB}" -f tests/00_supabase_stub.sql $(ls migrations/*.sql | sort | sed 's/^/-f /') >/dev/null
psql -q -t -A -v ON_ERROR_STOP=1 -d "${DB}" -f tests/10_rls_and_rules.sql 2>&1 | sed -E "s/^psql:[^ ]+ (NOTICE|ERROR):  //" | grep -E "^(ok -|FAIL|ALL TESTS|ERROR|CONTEXT)"
