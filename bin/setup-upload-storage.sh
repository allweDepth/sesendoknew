#!/bin/sh
set -eu
project_dir=$(CDPATH= cd -- "$(dirname -- "$0")/.." && pwd)
message_dir="$project_dir/storage/uploads/messages"
mkdir -p "$message_dir"
chmod 0733 "$message_dir"
if [ "$(uname -s)" = "Darwin" ] && id _www >/dev/null 2>&1; then
  chmod +a '_www allow list,add_file,search,add_subdirectory,delete_child,file_inherit,directory_inherit' "$message_dir" 2>/dev/null || true
fi
printf 'Upload storage ready: %s\n' "$message_dir"
