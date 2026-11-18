#!/usr/bin/env python3
"""Read-only checks for committed MySQL test-script execution prerequisites."""

import argparse
import json
import os
from pathlib import Path
import shutil
import subprocess
import sys

FILES = (
    "infra/local/mysql-init/01-create-testing-database.sh",
    "tests/infra/mysql-init-regression.sh",
)


def inspect(root: Path) -> list[dict]:
    findings = []

    def add(code, path, message):
        findings.append({"code": code, "path": path, "message": message})

    git = shutil.which("git")
    bash = shutil.which("bash")

    if not git:
        add("CI_GIT_MISSING", "", "Git is unavailable.")
        return findings

    if not bash:
        add("CI_BASH_MISSING", "", "Bash is unavailable.")

    for relative in FILES:
        path = root / relative

        if path.is_symlink() or not path.is_file():
            add("CI_SCRIPT_MISSING", relative,
                "Expected a regular, non-symlink script.")
            continue

        result = subprocess.run(
            [git, "-C", str(root), "ls-files", "--stage", "--", relative],
            capture_output=True,
            text=True,
            check=False,
        )

        if result.returncode != 0:
            add("CI_INDEX_READ_FAILED", relative, "Cannot inspect Git index.")
        else:
            entries = result.stdout.splitlines()
            if len(entries) != 1:
                add("CI_SCRIPT_NOT_TRACKED", relative,
                    "Expected exactly one tracked index entry.")
            else:
                fields = entries[0].split()
                if len(fields) < 3 or fields[2] != "0":
                    add("CI_INDEX_CONFLICT", relative,
                        "Index entry is invalid or unmerged.")
                elif fields[0] != "100755":
                    add("CI_INDEX_MODE", relative,
                        "Committed/index executable mode must be 100755.")

        if not os.access(path, os.X_OK):
            add("CI_WORKTREE_MODE", relative,
                "Working-tree script is not executable.")

        data = path.read_bytes()

        if b"\r" in data:
            add("CI_LINE_ENDINGS", relative, "Script must use LF line endings.")

        if not data.startswith(b"#!"):
            add("CI_SHEBANG", relative, "Script needs a shebang.")

        if bash:
            syntax = subprocess.run(
                [bash, "-n", str(path)],
                stdout=subprocess.DEVNULL,
                stderr=subprocess.DEVNULL,
                check=False,
            )
            if syntax.returncode:
                add("CI_SHELL_SYNTAX", relative, "Bash syntax check failed.")

    return findings


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument("--root", default=".")
    parser.add_argument("--report")
    args = parser.parse_args()

    findings = inspect(Path(args.root).resolve())
    report = {
        "schema": 1,
        "check": "ci-preflight",
        "status": "fail" if findings else "pass",
        "findings": findings,
    }
    encoded = json.dumps(report, indent=2) + "\n"

    if args.report:
        target = Path(args.report)
        target.parent.mkdir(parents=True, exist_ok=True)
        target.write_text(encoded, encoding="utf-8")

    sys.stdout.write(encoded)
    return 1 if findings else 0


if __name__ == "__main__":
    raise SystemExit(main())