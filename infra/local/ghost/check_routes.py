#!/usr/bin/env python3
"""Read-only local routing and first-level CSS/JS checks. Not gate approval."""

from html.parser import HTMLParser
import json
import sys
from pathlib import Path
import subprocess
import tempfile
from urllib.parse import urljoin, urlsplit

BASE = "http://ai.umu.test:8088"


def require(ok, message):
    if not ok:
        raise RuntimeError(message)


def get(path):
    url = urljoin(BASE, path)
    require(urlsplit(url).netloc == "ai.umu.test:8088", "Non-local URL refused")
    with tempfile.TemporaryDirectory(prefix="umu-route-check-") as folder:
        headers = Path(folder) / "headers"
        body = Path(folder) / "body"
        run = subprocess.run([
            "curl", "--noproxy", "*",
            "--resolve", "ai.umu.test:8088:127.0.0.1",
            "--connect-timeout", "3", "--max-time", "20",
            "--silent", "--show-error",
            "--dump-header", str(headers),
            "--output", str(body),
            "--write-out", "%{http_code}",
            url,
        ], capture_output=True, text=True)
        require(run.returncode == 0, f"Transport error for {path}: {run.stderr.strip()}")
        fields = {}
        for line in headers.read_text().splitlines():
            if ":" in line:
                key, value = line.split(":", 1)
                fields[key.lower()] = value.strip()
        return int(run.stdout), fields, body.read_bytes()


def expect(path, code):
    actual, headers, body = get(path)
    require(actual == code, f"{path}: HTTP {actual}, expected {code}")
    print(f"PASS: {path} HTTP {actual}")
    return headers, body


class Assets(HTMLParser):
    def __init__(self):
        super().__init__()
        self.urls = []

    def handle_starttag(self, tag, attrs):
        attrs = dict(attrs)
        if tag == "script" and attrs.get("src"):
            self.urls.append(attrs["src"])
        if tag == "link" and "stylesheet" in attrs.get("rel", "").split():
            if attrs.get("href"):
                self.urls.append(attrs["href"])


def check_html(path, prefix):
    headers, body = expect(path, 200)
    require("text/html" in headers.get("content-type", ""), f"{path}: not HTML")
    parser = Assets()
    parser.feed(body.decode("utf-8", errors="replace"))
    local = set()
    external = 0
    for reference in parser.urls:
        url = urlsplit(urljoin(BASE + path, reference))
        if (url.scheme, url.netloc) != ("http", "ai.umu.test:8088"):
            external += 1
            print(f"INFO: external asset not fetched: {url.geturl()}")
            continue
        require(url.path.startswith(prefix),
                f"{path}: local asset escapes expected prefix: {url.path}")
        local.add(url.path + ("?" + url.query if url.query else ""))
    require(bool(local), f"{path}: no local CSS/JS references found")
    for asset in sorted(local):
        asset_headers, asset_body = expect(asset, 200)
        kind = asset_headers.get("content-type", "").lower()
        require("text/html" not in kind, f"{asset}: returned HTML instead of an asset")
        require(bool(asset_body), f"{asset}: empty asset")
    print(f"INFO: {path}: {len(local)} local assets checked; "
          f"{external} external references not fetched")


def check_laravel():
    check_html("/", "/")
    check_html("/community", "/")
    _, body = expect("/health", 200)
    require(json.loads(body).get("status") == "ok", "Unexpected health payload")
    headers, _ = expect("/account", 302)
    location = headers.get("location")
    require(location in ("/", BASE, BASE + "/"),
            f"Anonymous account redirect does not return to the local home: {location!r}")
    print("PASS: anonymous account access redirects to local home")
    for path in ("/auth/google", "/auth/google/callback",
                 "/admin", "/admin/login", "/login", "/register", "/ai"):
        expect(path, 404)


def main():
    require(sys.argv[1:] in ([], ["--laravel-only"]),
            "Usage: check_routes.py [--laravel-only]")
    check_laravel()
    if sys.argv[1:] == ["--laravel-only"]:
        print("\nPASS: Laravel public, anonymous-account and disabled-route checks")
        return

    headers, _ = expect("/news", 308)
    require(headers.get("location") == BASE + "/news/",
            "Incorrect /news redirect destination")
    print("PASS: /news redirect preserves the configured hostname, port and prefix")

    check_html("/news/", "/news/")
    check_html("/news/ghost/", "/news/")
    print("\nPASS: initial routing and first-level local asset checks")
    print("LIMITS: no browser execution, nested CSS/module imports, external assets, "
          "authenticated-session checks, outage test or persistence test in this run")


if __name__ == "__main__":
    try:
        main()
    except Exception as exc:
        raise SystemExit(f"FAIL: {exc}")
