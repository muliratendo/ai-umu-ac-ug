import importlib.util
import json
import os
from pathlib import Path
import stat
import subprocess
import sys
import tempfile
import unittest

ROOT = Path(__file__).resolve().parents[2]
SCRIPT = ROOT / "scripts/maintenance/ci_preflight.py"
SPEC = importlib.util.spec_from_file_location("ci_preflight", SCRIPT)
PRE = importlib.util.module_from_spec(SPEC)
SPEC.loader.exec_module(PRE)


class PreflightTests(unittest.TestCase):
    def setUp(self):
        tmp = tempfile.TemporaryDirectory()
        self.addCleanup(tmp.cleanup)
        self.root = Path(tmp.name).resolve()
        self.git("init", "-q")
        self.git("config", "core.filemode", "true")
        self.git("config", "core.autocrlf", "false")
        for name in PRE.FILES:
            p = self.root / name
            p.parent.mkdir(parents=True, exist_ok=True)
            p.write_bytes(b"#!/usr/bin/env bash\n:\n")
            p.chmod(0o644)
        self.git("add", "--", *PRE.FILES)
        self.commit()
        self.name = PRE.FILES[0]
        self.path = self.root / self.name

    def git(self, *args, data=None):
        return subprocess.run(
            ["git", *args], cwd=self.root, input=data,
            capture_output=True, check=True,
        ).stdout

    def commit(self):
        self.git("-c", "user.name=Fixture", "-c",
                 "user.email=fixture@example.invalid", "commit", "-qm", "fixture")

    def codes(self):
        return {finding["code"] for finding in PRE.inspect(self.root)}

    def verify_modes(self, git_mode, fs_mode):
        before = self.git("ls-files", "--stage", "-z")
        contents = self.path.read_bytes()
        proc = subprocess.run(
            [sys.executable, str(SCRIPT), "--root", str(self.root)],
            capture_output=True, check=True,
        )
        report = json.loads(proc.stdout)
        self.assertEqual(report["status"], "pass")
        self.assertEqual(report["findings"], [])
        self.assertEqual(report["modes"][0], {
            "path": self.name,
            "committed": git_mode,
            "index": [{"mode": git_mode, "stage": "0"}],
            "worktree": fs_mode,
        })
        self.assertEqual(self.git("ls-files", "--stage", "-z"), before)
        self.assertEqual(f"{stat.S_IMODE(self.path.stat().st_mode):04o}", fs_mode)
        self.assertEqual(self.path.read_bytes(), contents)

    def test_0644_passes_and_stays_unchanged(self):
        self.verify_modes("100644", "0644")

    def test_0755_passes_and_stays_unchanged(self):
        self.path.chmod(0o755)
        self.git("update-index", "--chmod=+x", "--", self.name)
        self.commit()
        self.verify_modes("100755", "0755")

    def test_reports_distinct_committed_index_and_worktree_modes(self):
        self.git("update-index", "--chmod=+x", "--", self.name)
        modes = []
        self.assertEqual(PRE.inspect(self.root, modes), [])
        self.assertEqual(modes[0]["committed"], "100644")
        self.assertEqual(modes[0]["index"], [{"mode": "100755", "stage": "0"}])
        self.assertEqual(modes[0]["worktree"], "0644")

    def test_missing_file(self):
        self.path.unlink()
        self.assertIn("CI_SCRIPT_MISSING", self.codes())

    def test_missing_index_entry(self):
        self.git("update-index", "--force-remove", "--", self.name)
        self.assertIn("CI_SCRIPT_NOT_TRACKED", self.codes())

    def test_unmerged_entry(self):
        oid = self.git("rev-parse", f"HEAD:{self.name}").decode().strip()
        self.git("update-index", "--force-remove", "--", self.name)
        records = "".join(f"100644 {oid} {n}\t{self.name}\n" for n in (1, 2, 3))
        self.git("update-index", "--index-info", data=records.encode())
        self.assertIn("CI_INDEX_CONFLICT", self.codes())

    def test_nonregular_worktree_file(self):
        self.path.unlink()
        self.path.symlink_to(self.root / PRE.FILES[1])
        self.assertIn("CI_SCRIPT_NOT_REGULAR", self.codes())

    def test_nonregular_committed_and_index_modes(self):
        self.path.unlink()
        self.path.symlink_to("fixture-target")
        self.git("add", "--", self.name)
        self.commit()
        self.assertTrue({"CI_COMMITTED_MODE", "CI_INDEX_MODE"} <= self.codes())

    def test_invalid_content_is_fatal(self):
        for content, code in (
            (b"#!/usr/bin/env bash\r\n:\r\n", "CI_LINE_ENDINGS"),
            (b":\n", "CI_SHEBANG"),
            (b"#!/usr/bin/env bash\nif true; then\n", "CI_SHELL_SYNTAX"),
        ):
            with self.subTest(code=code):
                self.path.write_bytes(content)
                proc = subprocess.run(
                    [sys.executable, str(SCRIPT), "--root", os.fspath(self.root)],
                    capture_output=True,
                )
                self.assertEqual(proc.returncode, 1, proc.stderr)
                self.assertIn(code, {x["code"] for x in json.loads(proc.stdout)["findings"]})
