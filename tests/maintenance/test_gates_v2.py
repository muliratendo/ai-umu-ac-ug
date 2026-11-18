import json
from pathlib import Path
import subprocess
import sys
import tempfile
import unittest

SCRIPT = Path(__file__).resolve().parents[2] / "scripts/maintenance/gates_v2.py"
STATE = "docs/gates/state.json"
REPORT = "docs/gates/STATUS.md"
EVIDENCE = "docs/evidence/run.json"
BASE = {
    "scope": "Fixture only", "cause": "Regression fixture",
    "decision": "Check the ledger contract", "result": "pass",
    "observed_result": "Fixture records a result",
    "regression_test": "test_gates_v2.py", "limitations": "Not real CI evidence",
}


class GateTests(unittest.TestCase):
    def setUp(self):
        tmp = tempfile.TemporaryDirectory()
        self.addCleanup(tmp.cleanup)
        self.root = Path(tmp.name).resolve()
        self.git("init", "-q")
        self.write("app.txt", "before\n")
        self.git("add", "app.txt")
        self.commit("fixture")
        self.write("docs/gates/scopes.json", {"g1-ci": ["app.txt"]})
        self.write(EVIDENCE, BASE)

    def write(self, name, value):
        path = self.root / name
        path.parent.mkdir(parents=True, exist_ok=True)
        path.write_text(
            value if isinstance(value, str) else json.dumps(value), encoding="utf-8",
        )

    def git(self, *args):
        subprocess.run(["git", *args], cwd=self.root, capture_output=True, check=True)

    def commit(self, subject):
        self.git("-c", "user.name=Fixture", "-c", "user.email=fixture@example.invalid",
                 "commit", "-qm", subject)

    def cli(self, *args, code=0, diagnostic=None):
        result = subprocess.run(
            [sys.executable, str(SCRIPT), *args],
            cwd=self.root, capture_output=True, text=True,
        )
        self.assertEqual(result.returncode, code, result.stdout + result.stderr)
        if diagnostic:
            self.assertIn(diagnostic + ":", result.stderr)
        self.assertNotIn("Traceback", result.stderr)
        return result.stdout

    def record(self, kind="negative", **kwargs):
        return self.cli("record", "--task", "g1-ci", "--kind", kind,
                        "--result", "pass", "--by", "fixture",
                        "--evidence", EVIDENCE, **kwargs)

    def report(self):
        return (self.root / REPORT).read_text()

    def test_invalid_state_is_never_overwritten(self):
        for content, diagnostic in (
            ("", "GATE_STATE_EMPTY"),
            ("{", "GATE_STATE_INVALID_JSON"),
            ('{"schema":2,"events":{}}', "GATE_STATE_SCHEMA_INVALID"),
        ):
            with self.subTest(diagnostic=diagnostic):
                self.write(STATE, content)
                self.cli("check", code=1, diagnostic=diagnostic)
                self.cli("init", code=1, diagnostic="GATE_INIT_REFUSED")
                self.assertEqual((self.root / STATE).read_text(), content)

    def test_pending_evidence_cannot_be_recorded_as_pass(self):
        self.cli("init")
        self.write(EVIDENCE, {**BASE, "result": "pending"})
        self.record(code=1, diagnostic="GATE_EVIDENCE_RESULT_MISMATCH")

    def test_completion_requires_negative_evidence_and_scope_survives_docs_commit(self):
        self.cli("init")
        for kind in ("positive", "regression", "decision"):
            self.record(kind)
        self.assertIn("`g1-ci` — IN_PROGRESS", self.report())
        self.record()
        self.assertIn("`g1-ci` — COMPLETE", self.report())
        self.assertIn("`g1-signin` — REPORTED_PASS", self.report())
        self.write("README.md", "Unrelated documentation change")
        self.git("add", "README.md")
        self.commit("docs only")
        self.write("docs/evidence/extra.json", BASE)
        self.cli("check")
        self.assertIn("`g1-ci` — COMPLETE", self.report())

    def test_source_staleness_is_not_document_drift(self):
        self.cli("init")
        self.record()
        self.write("app.txt", "after\n")
        self.cli("check", code=1, diagnostic="GATE_VIEW_STALE")
        self.cli("refresh")
        self.assertIn("`g1-ci` — REVALIDATION_REQUIRED", self.report())
        self.record()
        self.cli("check")

    def test_manual_edit_cannot_be_hidden_with_refresh(self):
        self.cli("init")
        self.write(REPORT, "incorrect manual edit")
        self.cli("check", code=1, diagnostic="GATE_DOCUMENT_DRIFT")
        self.cli("refresh", code=1, diagnostic="GATE_DOCUMENT_DRIFT")

    def test_migration_preserves_browser_history_but_no_legacy_pass(self):
        old = {"task": "g1-browser", "kind": "manual", "result": "pass",
               "evidence": "docs/evidence/old.json"}
        self.write(STATE, {"schema": 1, "events": [old]})
        self.cli("migrate")
        new = json.loads((self.root / STATE).read_text())
        self.assertEqual(new["events"][0], old)
        self.assertIn("Gate 7: `g1-browser` — REVALIDATION_REQUIRED", self.report())
        self.assertNotIn("Gate 1: `g1-browser`", self.report())
        self.cli("check")

    def test_browser_cannot_record_pass_before_manus(self):
        self.write("docs/gates/scopes.json", {"g1-browser": ["app.txt"]})
        self.cli("init")
        self.cli("record", "--task", "g1-browser", "--kind", "manual",
                 "--result", "pass", "--by", "fixture", "--evidence", EVIDENCE,
                 code=1, diagnostic="GATE_BROWSER_DEFERRED")


if __name__ == "__main__":
    unittest.main()