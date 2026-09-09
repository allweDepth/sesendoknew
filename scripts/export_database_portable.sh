#!/usr/bin/env bash
set -euo pipefail

SESENDOK_DB_NAME="${SESENDOK_DB_NAME:-sesendoknew_db}"
SESENDOK_DB_USER="${SESENDOK_DB_USER:-root}"
SESENDOK_DB_SOCKET="${SESENDOK_DB_SOCKET:-/tmp/mysql.sock}"
SESENDOK_DUMP_FILE="${1:-config/sesendoknew_db.sql}"

mariadb-dump --socket="$SESENDOK_DB_SOCKET" -u"$SESENDOK_DB_USER" \
  --single-transaction --quick --hex-blob --routines --events --triggers \
  --default-character-set=utf8mb4 --result-file="$SESENDOK_DUMP_FILE" "$SESENDOK_DB_NAME"

# Hilangkan akun pembuat lokal dan gunakan collation yang tersedia luas pada
# MariaDB 10.x/11.x. Ini juga menghindari kerusakan parser VIEW phpMyAdmin.
perl -0pi -e 's{\/\*!50017 DEFINER=`[^`]+`@`[^`]+`\*\/\s*}{}g; s{\/\*!50013 DEFINER=`[^`]+`@`[^`]+` SQL SECURITY DEFINER \*\/}{\/\*!50013 SQL SECURITY INVOKER \*\/}g; s/utf8mb4_uca1400_ai_ci/utf8mb4_unicode_ci/g' "$SESENDOK_DUMP_FILE"
perl -pi -e 's/[ \t]+$//' "$SESENDOK_DUMP_FILE"

echo "Dump portabel dibuat: $SESENDOK_DUMP_FILE"
