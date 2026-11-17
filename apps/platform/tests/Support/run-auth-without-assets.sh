#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/../.."
if [[ -e bootstrap/cache/config.php ]]; then
  echo 'Stop: cached application config exists. Review it before testing.' >&2; exit 1
fi
stash="$(mktemp -d "${TMPDIR:-/tmp}/umu-auth-assets.XXXXXXXX")"
build_moved=0; hot_moved=0
restore() {
  result=$?
  trap - EXIT
  if (( build_moved )); then
    if [[ -e public/build || -L public/build ]]; then
      echo "Refusing to overwrite newly created public/build. Original remains in $stash" >&2; result=1
    else mv "$stash/build" public/build; fi
  fi
  if (( hot_moved )); then
    if [[ -e public/hot || -L public/hot ]]; then
      echo "Refusing to overwrite newly created public/hot. Original remains in $stash" >&2; result=1
    else mv "$stash/hot" public/hot; fi
  fi
  rmdir "$stash" 2>/dev/null || true
  exit "$result"
}
trap restore EXIT
trap 'exit 130' INT
trap 'exit 143' TERM
if [[ -e public/build || -L public/build ]]; then mv public/build "$stash/build"; build_moved=1; fi
if [[ -e public/hot || -L public/hot ]]; then mv public/hot "$stash/hot"; hot_moved=1; fi
php tests/Support/check-auth-database.php
php vendor/bin/phpunit -c auth-acceptance.phpunit.xml --exclude-group concurrency
