#!/bin/sh
set -eu
project_dir=$(CDPATH= cd -- "$(dirname -- "$0")/.." && pwd)
private_dir="$project_dir/storage/uploads"
public_dir="$project_dir/public/uploads"
mkdir -p "$private_dir/maps" "$private_dir/messages" "$public_dir/signature" "$public_dir/wilayah"

if [ "$(uname -s)" = "Darwin" ]; then
  web_user="${WEB_USER:-_www}"
  owner_user="${SUDO_USER:-$(id -un)}"
  id "$web_user" >/dev/null
  # Limit access to the web process and the project owner. Inheritance also
  # permits maintenance of folders subsequently created by the web process.
  for upload_dir in "$private_dir" "$public_dir"; do
    find "$upload_dir" -type d -exec chmod 0750 {} +
    for acl_user in "$web_user" "$owner_user"; do
      dir_acl="$acl_user allow read,write,execute,append,delete,delete_child,readattr,writeattr,readextattr,writeextattr,readsecurity,file_inherit,directory_inherit"
      file_acl="$acl_user allow read,write,append,delete,readattr,writeattr,readextattr,writeextattr,readsecurity"
      find "$upload_dir" -type d -exec chmod -a "$dir_acl" {} \; 2>/dev/null || true
      find "$upload_dir" -type d -exec chmod +a "$dir_acl" {} +
      find "$upload_dir" -type f -exec chmod -a "$file_acl" {} \; 2>/dev/null || true
      find "$upload_dir" -type f -exec chmod +a "$file_acl" {} +
    done
  done
else
  # Linux deployments must identify the PHP service group explicitly.
  : "${WEB_GROUP:?Set WEB_GROUP to the PHP service group before running this script}"
  chgrp -R "$WEB_GROUP" "$private_dir" "$public_dir"
  find "$private_dir" "$public_dir" -type d -exec chmod 2770 {} +
  find "$private_dir" "$public_dir" -type f -exec chmod 0660 {} +
fi
printf 'Upload storage ready: %s\n' "$private_dir" "$public_dir"
