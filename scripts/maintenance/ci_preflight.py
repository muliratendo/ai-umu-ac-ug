#!/usr/bin/env python3
"""Read-only checks for committed MySQL test-script execution prerequisites."""

import argparse
import json
import stat
from pathlib import Path
import shutil
import subprocess
import sys

FILES = (
    "infra/local/mysql-init/01-create-testing-database.sh",
    "tests/infra/mysql-init-regression.sh",
)


def inspect(root: Path, modes: list | None = None) -> list[dict]:
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
        reported = {
            "path": relative,
            "committed": None,
            "index": [],
            "worktree": None,
        }
        if modes is not None:
            modes.append(reported)

        def entries(*args):
            return subprocess.run(
                [git, "-C", str(root), *args, "--", relative],
                capture_output=True, text=True, check=False,
            )

        tree = entries("ls-tree", "HEAD")
        if tree.returncode:
            add("CI_TREE_READ_FAILED", relative, "Cannot inspect HEAD.")
        elif not tree.stdout:
            add("CI_COMMITTED_MISSING", relative, "Script is absent from HEAD.")
        else:
            reported["committed"] = tree.stdout.split()[0]
            if reported["committed"] not in {"100644", "100755"}:
                add("CI_COMMITTED_MODE", relative, "Expected a regular Git file.")

        index = entries("ls-files", "--stage")
        if index.returncode:
            add("CI_INDEX_READ_FAILED", relative, "Cannot inspect Git index.")
        else:
            metadata = [
                line.partition(chr(9))[0].split()
                for line in index.stdout.splitlines()
            ]
            reported["index"] = [
                {"mode": row[0], "stage": row[2]}
                for row in metadata if len(row) == 3
            ]
            if not metadata:
                add("CI_SCRIPT_NOT_TRACKED", relative, "No index entry.")
            elif len(metadata) != 1 or len(metadata[0]) != 3 or metadata[0][2] != "0":
                add("CI_INDEX_CONFLICT", relative, "Index entry is invalid or unmerged.")
            elif metadata[0][0] not in {"100644", "100755"}:
                add("CI_INDEX_MODE", relative, "Expected a regular Git file.")

        try:
            info = path.lstat()
        except FileNotFoundError:
            add("CI_SCRIPT_MISSING", relative, "Script is missing from the worktree.")
            continue
        except OSError:
            add("CI_SCRIPT_READ_FAILED", relative, "Cannot inspect worktree script.")
            continue

        reported["worktree"] = f"{stat.S_IMODE(info.st_mode):04o}"
        if not stat.S_ISREG(info.st_mode) or path.resolve() != path:
            add("CI_SCRIPT_NOT_REGULAR", relative, "Expected a regular, non-symlink script.")
            continue
        try:
            data = path.read_bytes()
        except OSError:
            add("CI_SCRIPT_READ_FAILED", relative, "Cannot read worktree script.")
            continue
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

    modes = []
    findings = inspect(Path(args.root).resolve(), modes)
    report = {
        "schema": 1,
        "check": "ci-preflight",
        "status": "fail" if findings else "pass",
        "modes": modes,
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