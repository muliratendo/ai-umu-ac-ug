"""Prove that preflight detects defects, rather than only accepting good files."""

import importlib.util
import os
from pathlib import Path
import shutil
import subprocess
import tempfile
import unittest

ROOT = Path(__file__).resolve().parents[2]
SPEC = importlib.util.spec_from_file_location(
    "ci_preflight",
    ROOT / "scripts/maintenance/ci_preflight.py",
)
MODULE = importlib.util.module_from_spec(SPEC)
SPEC.loader.exec_module(MODULE)


@unittest.skipUnless(
    os.name == "posix" and shutil.which("git") and shutil.which("bash"),
    "Requires a Linux/POSIX Git and Bash environment.",
)
class PreflightTests(unittest.TestCase):
    def setUp(self):
        self.temp = tempfile.TemporaryDirectory()
        self.addCleanup(self.temp.cleanup)
        self.root = Path(self.temp.name)

        self.git("init", "-q")
        self.git("config", "core.filemode", "true")

        for relative in MODULE.FILES:
            path = self.root / relative
            path.parent.mkdir(parents=True, exist_ok=True)
            path.write_bytes(b"#!/usr/bin/env bash\nset -euo pipefail\n:\n")
            path.chmod(0o755)
            self.git("add", "--", relative)
            self.git("update-index", "--chmod=+x", "--", relative)

    def git(self, *args):
        subprocess.run(
            ["git", "-C", str(self.root), *args],
            check=True,
            stdout=subprocess.DEVNULL,
            stderr=subprocess.DEVNULL,
        )

    def codes(self):
        return {item["code"] for item in MODULE.inspect(self.root)}

    def test_good_scripts_pass(self):
        self.assertEqual(MODULE.inspect(self.root), [])

    def test_non_executable_index_is_detected(self):
        self.git("update-index", "--chmod=-x", "--", MODULE.FILES[0])
        self.assertIn("CI_INDEX_MODE", self.codes())

    def test_non_executable_worktree_is_detected(self):
        (self.root / MODULE.FILES[0]).chmod(0o644)
        self.assertIn("CI_WORKTREE_MODE", self.codes())

    def test_crlf_is_detected(self):
        path = self.root / MODULE.FILES[0]
        path.write_bytes(b"#!/usr/bin/env bash\r\n:\r\n")
        self.assertIn("CI_LINE_ENDINGS", self.codes())

    def test_invalid_bash_is_detected(self):
        path = self.root / MODULE.FILES[0]
        path.write_bytes(b"#!/usr/bin/env bash\nif true; then\n")
        self.assertIn("CI_SHELL_SYNTAX", self.codes())

    def test_missing_script_is_detected(self):
        (self.root / MODULE.FILES[0]).unlink()
        self.assertIn("CI_SCRIPT_MISSING", self.codes())


if __name__ == "__main__":
    unittest.main()