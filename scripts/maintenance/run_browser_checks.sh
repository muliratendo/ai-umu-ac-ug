#!/usr/bin/env bash
# Run public-page checks only. Does not sign in or mutate application data.
set -euo pipefail

ROOT="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$ROOT"

: "${BROWSER_BASE_URL:?Set BROWSER_BASE_URL to an approved local/staging origin}"

python3 - <<'PY'
import os
import sys
from urllib.parse import urlsplit

url = urlsplit(os.environ["BROWSER_BASE_URL"])
local = url.hostname in {"localhost", "127.0.0.1", "::1"}
approved_staging = os.environ.get("BROWSER_STAGING_HOST", "")

if (
    url.username or url.password or url.query or url.fragment
    or url.path not in {"", "/"}
    or not url.hostname
):
    sys.exit("BROWSER_TARGET_INVALID")

if url.hostname == "ai.umu.ac.ug":
    sys.exit("BROWSER_PRODUCTION_TARGET_REFUSED")

if local:
    if url.scheme not in {"http", "https"}:
        sys.exit("BROWSER_SCHEME_INVALID")
elif not (
    url.scheme == "https"
    and approved_staging
    and url.hostname == approved_staging
):
    sys.exit("BROWSER_STAGING_APPROVAL_REQUIRED")
PY

mkdir -p .quality/private
chmod 700 .quality/private

cd apps/platform

test -x node_modules/.bin/playwright || {
  echo "BROWSER_DEPENDENCY_MISSING"
  exit 2
}

node_modules/.bin/playwright test \
  --config tests/Browser/playwright.config.mjs