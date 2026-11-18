import os
from pathlib import Path
import stat
import subprocess
import tempfile
import unittest

ROOT = Path(__file__).resolve().parents[2]
WRAPPER = ROOT / "scripts/maintenance/run_mysql_regression_ci.sh"
INIT = "infra/local/mysql-init/01-create-testing-database.sh"
HARNESS = "tests/infra/mysql-init-regression.sh"


class WrapperTests(unittest.TestCase):
    def exercise(self, modes, stub_exit, host="127.0.0.1"):
        with tempfile.TemporaryDirectory() as directory:
            root = Path(directory)

            def git(*args):
                return subprocess.run(
                    ["git", *args], cwd=root, capture_output=True, check=True,
                ).stdout

            git("init", "-q")
            git("config", "core.filemode", "true")
            git("config", "core.autocrlf", "false")
            bodies = {
                INIT: "#!/usr/bin/env bash\nexit 0\n",
                HARNESS: f"""#!/usr/bin/env bash
set -eu
test -x {INIT}
test -x {HARNESS}
{INIT}
printf '%s\\n' STUB_RAN >> stub-runs
exit {stub_exit}
""",
            }
            for name, mode in zip((INIT, HARNESS), modes):
                path = root / name
                path.parent.mkdir(parents=True, exist_ok=True)
                path.write_text(bodies[name])
                path.chmod(mode)
            git("add", "--", INIT, HARNESS)
            git("-c", "user.name=Fixture", "-c", "user.email=fixture@example.invalid",
                "commit", "-qm", "fixture")
            index_before = git("ls-files", "--stage", "-z")

            proc = subprocess.run(
                ["bash", str(WRAPPER)], cwd=root, capture_output=True,
                text=True, timeout=15,
                env={
                    "PATH": os.environ["PATH"],
                    "HOME": str(root),
                    "GITHUB_ACTIONS": "true",
                    "MYSQL_INIT_MYSQL_HOST": host,
                    "MYSQL_INIT_MYSQL_PORT": "3306",
                },
            )
            logs = proc.stdout + proc.stderr
            self.assertEqual(git("ls-files", "--stage", "-z"), index_before)
            for name, mode in zip((INIT, HARNESS), modes):
                self.assertEqual(stat.S_IMODE((root / name).stat().st_mode), mode)
                self.assertEqual((root / name).read_text(), bodies[name])

            marker = root / "stub-runs"
            if host != "127.0.0.1":
                self.assertEqual(proc.returncode, 2, logs)
                self.assertIn("DB_TARGET_UNSAFE", logs)
                self.assertFalse(marker.exists())
                self.assertNotIn("MYSQL_MODE_ENABLED", logs)
                return

            self.assertEqual(proc.returncode, stub_exit, logs)
            self.assertEqual(marker.read_text(), "STUB_RAN\n")
            for name, mode in zip((INIT, HARNESS), modes):
                self.assertIn(f"MYSQL_MODE_BEFORE path={name}", logs)
                if mode == 0o644:
                    self.assertIn(f"MYSQL_MODE_ENABLED path={name} mode=744", logs)
                    self.assertIn(f"MYSQL_MODE_RESTORED path={name} mode=644", logs)
                else:
                    self.assertIn(f"MYSQL_MODE_UNCHANGED path={name}", logs)
                    self.assertNotIn(f"MYSQL_MODE_ENABLED path={name}", logs)
                    self.assertNotIn(f"MYSQL_MODE_RESTORED path={name}", logs)

    def test_0644_restored_on_success(self):
        self.exercise((0o644, 0o644), 0)

    def test_0644_restored_on_failure_and_exit_is_preserved(self):
        self.exercise((0o644, 0o644), 42)

    def test_0755_stays_executable(self):
        for status in (0, 42):
            with self.subTest(status=status):
                self.exercise((0o755, 0o755), status)

    def test_mixed_modes_are_restored_individually(self):
        self.exercise((0o755, 0o644), 42)

    def test_unsafe_target_never_runs_stub(self):
        self.exercise((0o644, 0o644), 0, host="192.0.2.1")
