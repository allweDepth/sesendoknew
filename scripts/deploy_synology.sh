#!/usr/bin/env bash
set -euo pipefail

REMOTE_USER="${REMOTE_USER:-alwimansyur1980}"
REMOTE_HOST="${REMOTE_HOST:-192.168.1.55}"
REMOTE_ROOT="${REMOTE_ROOT:-/volume1/web/sesendoknew}"
SSH_OPTS=(-o StrictHostKeyChecking=accept-new)

FILES=(
  app/Core/Auth.php
  app/Core/DB.php
  app/Services/DynamicTableService.php
  app/Views/partials/sidebar.php
  config/database.php
  public/assets/css/modern-tables.css
  public/assets/js/config/ui-config.js
  public/assets/js/engine/table-manager.js
)

rsync -av --progress -e "ssh ${SSH_OPTS[*]}" "${FILES[@]}" "${REMOTE_USER}@${REMOTE_HOST}:${REMOTE_ROOT}/"
printf 'Deployment selesai ke %s@%s:%s\n' "$REMOTE_USER" "$REMOTE_HOST" "$REMOTE_ROOT"