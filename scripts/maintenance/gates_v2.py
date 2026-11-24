#!/usr/bin/env python3
"""Evidence ledger. Explicit writes only; check never rewrites tracked files."""

import argparse
from datetime import datetime, timezone
import fcntl
import hashlib
import json
import os
from pathlib import Path
import subprocess
import sys
import tempfile

LABELS = (
    "Complete current SSO and CI verification",
    "Approve separate administrative grants, grant/revoke authority, publishing and audit policy",
    "Test protected reads and writes with Twill disabled",
    "Run Ghost locally under /news/ and verify service isolation",
    "Verify Ghost editorial, member and test newsletter flows",
    "Separately approve and implement leadership content",
    "Integrate Manus; then complete browser and accessibility acceptance",
    "Verify security safeguards, monitoring and rollback",
)
CODE = ("positive", "negative", "regression", "decision")
TASKS = {
    "g1-ci": (1, CODE),
    "g1-signin": (1, CODE),
    "g1-revoke": (1, CODE),
    "g1-disable": (1, CODE),
    "g2-policy": (2, ("approval", "negative", "decision")),
    "g3-authorization": (3, CODE),
    "g4-ghost-local": (4, CODE),
    "g5-ghost-editorial": (5, CODE),
    "g6-approval": (6, ("approval", "decision")),
    "g6-leadership": (6, CODE),
    "g7-designs": (7, CODE),
    "g1-browser": (7, CODE + ("manual",)),  # Retain historical task ID.
    "g8-ddos-cache": (8, CODE + ("manual",)),
    "g8-mfa-privileges": (8, CODE + ("manual",)),
    "g8-auth-sessions": (8, CODE + ("manual",)),
    "g8-capacity": (8, CODE + ("manual",)),
    "g8-monitoring": (8, CODE + ("manual",)),
    "g8-rollout-recovery": (8, CODE + ("manual",)),
}
RESULTS = {"pass", "fail", "blocked", "pending"}
STATE = "docs/gates/state.json"
VIEWS = ("docs/gates/STATUS.md", "docs/gates/RESPONSE_STATUS.md")
SCOPE_FILE = "docs/gates/scopes.json"


class GateError(Exception):
    pass


def fail(code, detail):
    raise GateError(f"{code}: {detail}")


def git(root, *args):
    p = subprocess.run(
        ["git", "-C", str(root), *args],
        capture_output=True, check=False,
    )
    if p.returncode:
        fail("GATE_GIT_ERROR", f"git {args[0]} failed (exit {p.returncode})")
    return p.stdout


def read_json(path, prefix):
    if path.is_symlink() or not path.is_file():
        fail(f"{prefix}_MISSING", path.name)
    data = path.read_bytes()
    if not data:
        fail(f"{prefix}_EMPTY", path.name)
    try:
        return json.loads(data.decode("utf-8"))
    except (UnicodeDecodeError, json.JSONDecodeError) as exc:
        fail(f"{prefix}_INVALID_JSON", f"{path.name}: {exc}")


def sha(data):
    return hashlib.sha256(data).hexdigest()


def display(value):
    # Keep ledger metadata from breaking generated Markdown table rows.
    return str(value).replace("|", r"\|").replace("\r", " ").replace("\n", " ")


def atomic(path, data):
    path.parent.mkdir(parents=True, exist_ok=True)
    fd, name = tempfile.mkstemp(dir=path.parent)
    try:
        with os.fdopen(fd, "wb") as stream:
            stream.write(data)
            stream.flush()
            os.fsync(stream.fileno())
        os.replace(name, path)
    finally:
        Path(name).unlink(missing_ok=True)


def dump(obj):
    return (json.dumps(obj, indent=2, ensure_ascii=False) + "\n").encode()


def load_state(root):
    state = read_json(root / STATE, "GATE_STATE")
    if (
        not isinstance(state, dict)
        or type(state.get("schema")) is not int
        or state["schema"] not in (1, 2)
        or not isinstance(state.get("events"), list)
        or any(not isinstance(e, dict) for e in state["events"])
    ):
        fail("GATE_STATE_SCHEMA_INVALID", "expected schema 1 or 2 and an events array")

    if state["schema"] == 2:
        if not isinstance(state.get("view_hashes"), dict):
            fail("GATE_STATE_SCHEMA_INVALID", "missing view_hashes")
        for e in state["events"]:
            if e.get("format") == 2 and (
                e.get("task") not in TASKS
                or e.get("kind") not in TASKS[e["task"]][1]
                or not isinstance(e.get("result"), str)
                or e["result"] not in RESULTS
                or any(not isinstance(e.get(k), str) or not e[k] for k in (
                    "observed_commit", "fingerprint", "evidence",
                    "evidence_sha256", "by", "at",
                ))
            ):
                fail("GATE_STATE_SCHEMA_INVALID", "invalid v2 evidence event")
    return state


def scope_paths(root, task):
    config = read_json(root / SCOPE_FILE, "GATE_SCOPES")
    if not isinstance(config, dict):
        fail("GATE_SCOPES_SCHEMA_INVALID", "expected a task-to-paths object")
    paths = config.get(task)
    if paths is None:
        return None
    if not isinstance(paths, list) or not paths:
        fail("GATE_SCOPES_SCHEMA_INVALID", f"{task}: expected a nonempty path list")

    checked = set()
    for name in paths:
        if not isinstance(name, str):
            fail("GATE_SCOPE_PATH_INVALID", task)
        p = Path(name)
        if (
            p.is_absolute() or ".." in p.parts or p.as_posix() != name
            or name == "." or name.startswith(("docs/gates/", "docs/evidence/", ".quality/"))
            or any(part.startswith(".env") for part in p.parts)
            or any(part in {"vendor", "node_modules", ".git"} for part in p.parts)
        ):
            fail("GATE_SCOPE_PATH_INVALID", name)
        checked.add(name)
    return sorted(checked)


def fingerprint(root, task):
    paths = scope_paths(root, task)
    if paths is None:
        return None
    h = hashlib.sha256()
    for name in paths:
        path = root / name
        if path.resolve() != path or path.is_symlink():
            fail("GATE_SCOPE_PATH_INVALID", name)
        if not path.is_file():
            return None
        # Record the staged mode separately from worktree contents and permissions.
        # This catches staged-only changes to executable bits in CI prerequisites.
        entry = git(root, "ls-files", "--stage", "-z", "--", name).split(b"\0")[0]
        mode = entry.split(b" ", 1)[0] if entry else b"untracked"
        h.update(dump([name, mode.decode(), bool(path.stat().st_mode & 0o111),
                       sha(path.read_bytes())]))
    return h.hexdigest()


def evidence_path(root, name):
    path = root / name
    if (
        not name.startswith("docs/evidence/") or Path(name).suffix != ".json"
        or not path.is_relative_to(root) or path.resolve() != path
        or not path.is_file()
    ):
        fail("GATE_EVIDENCE_PATH_INVALID", name)
    return path


def task_status(root, state, task):
    fp = fingerprint(root, task)
    events = [e for e in state["events"] if e.get("task") == task]
    current = {}
    for e in events:
        if e.get("format") != 2 or fp is None or e["fingerprint"] != fp:
            continue

        # Select the latest result first. Missing or edited evidence must not
        # cause an older PASS to regain authority.
        current[e["kind"]] = e

    for e in current.values():
        try:
            path = evidence_path(root, e["evidence"])
        except GateError:
            return "REVALIDATION_REQUIRED"
        if sha(path.read_bytes()) != e["evidence_sha256"]:
            return "REVALIDATION_REQUIRED"

    if any(e["result"] == "fail" for e in current.values()):
        return "FAILED"
    if all(current.get(k, {}).get("result") == "pass" for k in TASKS[task][1]):
        return "COMPLETE"
    if current:
        return "IN_PROGRESS"
    if any(e.get("kind") != "reported" for e in events):
        return "REVALIDATION_REQUIRED"
    if any(e.get("kind") == "reported" and e.get("result") == "pass" for e in events):
        return "REPORTED_PASS"
    if task == "g1-browser":
        return "DEFERRED_UNTIL_MANUS"
    return "PENDING"


def documents(root, state):
    statuses = {task: task_status(root, state, task) for task in TASKS}
    table = "## Current gates\n\n| Gate | Work | Status |\n|---|---|---|\n"
    previous_complete = True
    for number, label in enumerate(LABELS, 1):
        values = [statuses[t] for t, (n, _) in TASKS.items() if n == number]
        # Gate 3 may verify authorization without Gate 2 policy approval.
        # Keep the cumulative prerequisite for every later gate.
        prerequisites_complete = previous_complete
        if number == 3:
            prerequisites_complete = all(
                statuses[task] == "COMPLETE"
                for task, (gate, _) in TASKS.items() if gate == 1
            )
        if number == 2 and statuses["g2-policy"] != "COMPLETE":
            status = "FAILED" if "FAILED" in values else "AWAITING_APPROVAL"
        elif not prerequisites_complete:
            status = "BLOCKED"
        elif "FAILED" in values:
            status = "FAILED"
        elif all(value == "COMPLETE" for value in values):
            status = "COMPLETE"
        else:
            status = "IN_PROGRESS" if number in (1, 3) else "PENDING"
        table += f"| {number} | {label} | {status} |\n"
        previous_complete = previous_complete and status == "COMPLETE"

    detail = (
        "# Gate checklist\n\n" + table +
        "\n`g1-browser` retains its historical ID; it now belongs to Gate 7. "
        "Final acceptance follows Manus integration. Rescheduling is not a pass.\n"
        "\n## Tasks\n\n"
    )
    for task, (number, required) in TASKS.items():
        status = statuses[task]
        tick = "x" if status == "COMPLETE" else " "
        detail += f"- [{tick}] Gate {number}: `{task}` — {status}; requires {', '.join(required)}.\n"
    detail += "\n## Evidence history\n\n"
    for e in state["events"]:
        detail += (
            f"- {display(e.get('at', 'legacy'))} — "
            f"{display(e.get('task', 'ledger'))}: "
            f"{display(e.get('kind', 'event'))}/{display(e.get('result', 'recorded'))}; "
            f"{display(e.get('evidence', e.get('note', 'no evidence path')))}.\n"
        )
    return dict(zip(VIEWS, (detail.encode(), table.encode())))


def verify_saved_views(root, state):
    # Compare with the last saved rendering, not with today's source files.
    # This separates manual edits/interrupted writes from evidence staleness.
    for name in VIEWS:
        path = root / name
        if (
            path.is_symlink() or not path.is_file()
            or sha(path.read_bytes()) != state["view_hashes"].get(name)
        ):
            fail("GATE_DOCUMENT_DRIFT", f"{name}; inspect before regenerating")


def save(root, state):
    views = documents(root, state)
    state["view_hashes"] = {name: sha(data) for name, data in views.items()}
    # If interrupted, the next check fails on mismatched view hashes.
    atomic(root / STATE, dump(state))
    for name, data in views.items():
        atomic(root / name, data)


def stamp():
    return datetime.now(timezone.utc).isoformat()


def backup(root):
    folder = Path(tempfile.mkdtemp(prefix="gates-", dir=root / ".quality"))
    for name in (STATE, *VIEWS):
        path = root / name
        if path.is_symlink():
            fail("GATE_BACKUP_REFUSED", name)
        if path.exists():
            atomic(folder / path.name, path.read_bytes())
    print(f"Backup: {folder.relative_to(root)}")


def validate_evidence(root, args):
    if not args.evidence or not args.by or not args.by.strip():
        fail("GATE_ARGUMENTS_REQUIRED", "provide --by and --evidence")
    path = evidence_path(root, args.evidence)
    data = read_json(path, "GATE_EVIDENCE")
    required = (
        "scope", "cause", "decision", "result",
        "observed_result", "regression_test", "limitations",
    )
    if not isinstance(data, dict) or any(
        not isinstance(data.get(key), str) or not data[key].strip() for key in required
    ):
        fail("GATE_EVIDENCE_SCHEMA_INVALID", "required strings: " + ", ".join(required))
    if data["result"] != args.result:
        fail("GATE_EVIDENCE_RESULT_MISMATCH", "JSON result must equal --result")
    return sha(path.read_bytes())


def run(root, args):
    if args.command == "init":
        if any((root / name).exists() or (root / name).is_symlink()
               for name in (STATE, *VIEWS)):
            fail("GATE_INIT_REFUSED", "gate files already exist; inspect or migrate")
        state = {"schema": 2, "events": [
            {"task": task, "kind": "reported", "result": "pass",
             "at": stamp(), "note": "User-reported; not independently reproduced."}
            for task in ("g1-signin", "g1-revoke", "g1-disable")
        ]}
        save(root, state)
        return

    state = load_state(root)
    if args.command == "migrate":
        if state["schema"] != 1:
            fail("GATE_MIGRATION_NOT_REQUIRED", "ledger already uses schema 2")
        backup(root)
        state["schema"] = 2
        state["events"].append({
            "at": stamp(), "task": "g1-browser", "kind": "schedule_migration",
            "result": "pending",
            "note": "Moved Gate 1 → Gate 7 after Manus; legacy evidence preserved.",
        })
        save(root, state)
        return
    if state["schema"] != 2:
        fail("GATE_MIGRATION_REQUIRED", "review and run migrate first")

    if args.command == "reconcile":
        if args.result != "blocked":
            fail("GATE_RECONCILIATION_RESULT", "use --result blocked; reconciliation grants no pass")
        digest = validate_evidence(root, args)
        # Only explicit reviewed reconciliation can replace altered generated views.
        backup(root)
        state["events"].append({
            "at": stamp(), "kind": "reconciliation", "result": "recorded",
            "by": args.by, "evidence": args.evidence, "evidence_sha256": digest,
            "observed_commit": git(root, "rev-parse", "HEAD").decode().strip(),
        })
        save(root, state)
        return

    verify_saved_views(root, state)
    if args.command == "check":
        current = documents(root, state)
        print(current[VIEWS[1]].decode(), end="")
        if any(sha(data) != state["view_hashes"][name] for name, data in current.items()):
            fail("GATE_VIEW_STALE", "evidence status changed; inspect and run refresh")
        return
    if args.command == "refresh":
        save(root, state)
        return

    if args.task not in TASKS or args.kind not in TASKS[args.task][1]:
        fail("GATE_ARGUMENTS_REQUIRED", "specify a valid task and its evidence kind")
    digest = validate_evidence(root, args)
    fp = fingerprint(root, args.task)
    if fp is None:
        fail("GATE_SCOPE_UNAVAILABLE", "configure all reviewed inputs for this task")
    if args.task == "g1-browser" and args.result == "pass":
        if task_status(root, state, "g7-designs") != "COMPLETE":
            fail("GATE_BROWSER_DEFERRED", "record Manus integration evidence first")
    state["events"].append({
        "format": 2, "at": stamp(), "task": args.task, "kind": args.kind,
        "result": args.result, "by": args.by, "evidence": args.evidence,
        "evidence_sha256": digest,
        "observed_commit": git(root, "rev-parse", "HEAD").decode().strip(),
        "fingerprint": fp,
    })
    save(root, state)


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("command", choices=(
        "init", "migrate", "record", "check", "refresh", "reconcile",
    ))
    parser.add_argument("--task", choices=TASKS)
    parser.add_argument("--kind")
    parser.add_argument("--result", choices=sorted(RESULTS))
    parser.add_argument("--by")
    parser.add_argument("--evidence")
    args = parser.parse_args()
    try:
        root = Path(git(Path.cwd(), "rev-parse", "--show-toplevel").decode().strip()).resolve()
        quality = root / ".quality"
        quality.mkdir(mode=0o700, exist_ok=True)
        with (quality / "gates-v2.lock").open("a") as lock:
            fcntl.flock(lock, fcntl.LOCK_EX)
            run(root, args)
    except GateError as exc:
        print(exc, file=sys.stderr)
        return 1
    except OSError as exc:
        print(f"GATE_IO_ERROR: {exc}", file=sys.stderr)
        return 1
    return 0


if __name__ == "__main__":
    raise SystemExit(main())