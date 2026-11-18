#!/usr/bin/env python3
"""Evidence ledger and deterministic gate documentation. No task auto-approval."""

import argparse
from datetime import datetime, timezone
import fcntl
import hashlib
import json
import os
from pathlib import Path
import subprocess
import tempfile

ROOT = Path(__file__).resolve().parents[2]
DIRECTORY = ROOT / "docs/gates"
STATE = DIRECTORY / "state.json"
STATUS = DIRECTORY / "STATUS.md"
RESPONSE = DIRECTORY / "RESPONSE_STATUS.md"

LABELS = [
    "Complete current SSO, CI, browser and accessibility verification",
    "Agree the minimal administrative-access policy",
    "Implement and regression-test authorization with Twill disabled",
    "Integrate and enable Twill locally after authorization passes",
    "Implement draft-to-public news publication",
    "Approve and implement leadership content separately",
    "Integrate Manus designs and repeat responsive/accessibility checks",
    "Harden and test security safeguards",
]

CODE = ["positive", "negative", "regression", "decision"]

TASKS = {
    "g1-ci": (1, CODE),
    "g1-signin": (1, CODE),
    "g1-revoke": (1, CODE),
    "g1-disable": (1, CODE),
    "g1-browser": (1, CODE + ["manual"]),
    "g2-policy": (2, ["approval", "negative", "decision"]),
    "g3-authorization": (3, CODE),
    "g4-twill": (4, CODE),
    "g5-news": (5, CODE),
    "g6-approval": (6, ["approval", "decision"]),
    "g6-leadership": (6, CODE),
    "g7-designs": (7, CODE + ["manual"]),
    "g8-ddos-cache": (8, CODE + ["manual"]),
    "g8-mfa-privileges": (8, CODE + ["manual"]),
    "g8-auth-sessions": (8, CODE + ["manual"]),
    "g8-capacity": (8, CODE + ["manual"]),
    "g8-monitoring": (8, CODE + ["manual"]),
    "g8-rollout-recovery": (8, CODE + ["manual"]),
}

KINDS = set(CODE + ["manual", "approval", "reported"])


def now():
    return datetime.now(timezone.utc).isoformat()


def git(*args):
    return subprocess.check_output(
        ["git", "-C", str(ROOT), *args], text=True
    ).strip()


def snapshot():
    """Bind local evidence to tracked/nonignored source, including dirty files."""
    names = subprocess.check_output(
        ["git", "-C", str(ROOT), "ls-files",
         "--cached", "--others", "--exclude-standard", "-z"]
    ).decode().split("\0")
    digest = hashlib.sha256()
    for name in sorted(set(filter(None, names))):
        if name.startswith(("docs/gates/", ".quality/")):
            continue
        path = ROOT / name
        digest.update(name.encode() + b"\0")
        if path.is_symlink():
            digest.update(b"SYMLINK:" + os.readlink(path).encode())
        elif path.is_file():
            digest.update(path.read_bytes())
        else:
            digest.update(b"MISSING")
    return digest.hexdigest()


def atomic(path, text):
    path.parent.mkdir(parents=True, exist_ok=True)
    fd, temporary = tempfile.mkstemp(dir=path.parent)
    try:
        with os.fdopen(fd, "w", encoding="utf-8") as stream:
            stream.write(text)
            stream.flush()
            os.fsync(stream.fileno())
        os.replace(temporary, path)
    finally:
        if os.path.exists(temporary):
            os.unlink(temporary)


def safe_text(value):
    return str(value).replace("|", "\\|").replace("\n", " ")


def task_status(state, task):
    records = [r for r in state["events"] if r.get("task") == task]
    current = [
        r for r in records
        if r.get("head") == git("rev-parse", "HEAD")
        and r.get("snapshot") == snapshot()
        and r.get("kind") != "reported"
    ]
    latest = {}
    for record in current:
        latest[record["kind"]] = record
    if any(r["result"] == "fail" for r in latest.values()):
        return "FAILED"
    if all(latest.get(k, {}).get("result") == "pass"
           for k in TASKS[task][1]):
        return "COMPLETE"
    if current:
        return "IN_PROGRESS"
    if any(r.get("kind") != "reported" for r in records):
        return "REVALIDATION_REQUIRED"
    if records:
        return "REPORTED_PASS"
    return "PENDING"


def gate_rows(state):
    rows = []
    task_states = {task: task_status(state, task) for task in TASKS}
    completed = {}
    for number, label in enumerate(LABELS, 1):
        statuses = [
            task_states[task] for task, (gate, _) in TASKS.items()
            if gate == number
        ]
        if all(s == "COMPLETE" for s in statuses):
            status = "COMPLETE"
        elif "FAILED" in statuses:
            status = "FAILED"
        elif number == 1:
            status = "IN_PROGRESS"
        elif number == 2:
            status = "AWAITING_APPROVAL"
        elif not completed.get(number - 1, False):
            status = "BLOCKED"
        else:
            status = "PENDING"
        completed[number] = status == "COMPLETE"
        rows.append((number, label, status))
    return rows, task_states


def documents(state):
    rows, task_states = gate_rows(state)
    table = (
        "| Gate | Work | Status |\n|---|---|---|\n"
        + "".join(f"| {n} | {label} | {status} |\n"
                  for n, label, status in rows)
    )
    response = (
        "<!-- GATES:BEGIN -->\n## Current gates\n\n"
        + table + "<!-- GATES:END -->\n"
    )
    status = "# Gate checklist\n\n" + table + "\n## Tasks\n\n"
    for task, (_, requirements) in TASKS.items():
        done = task_states[task] == "COMPLETE"
        status += (
            f"- [{'x' if done else ' '}] `{task}`: {task_states[task]}; "
            f"requires {', '.join(requirements)}.\n"
        )
    status += "\n## Evidence and decisions\n\n"
    for record in state["events"]:
        status += (
            f"### {safe_text(record['id'])}\n\n"
            f"- Recorded: {safe_text(record['at'])}\n"
            f"- Task: {safe_text(record.get('task', 'reconciliation'))}\n"
            f"- Kind/result: {safe_text(record.get('kind', 'reconciliation'))}"
            f"/{safe_text(record.get('result', 'recorded'))}\n"
            f"- By: {safe_text(record['by'])}\n"
            f"- Details: `{safe_text(record.get('evidence', 'initial report'))}`\n"
            f"- Evidence SHA-256: "
            f"`{safe_text(record.get('evidence_sha256', 'not supplied'))}`\n\n"
        )
    return {STATUS: status, RESPONSE: response}


def drift(state):
    return [
        str(path.relative_to(ROOT))
        for path, expected in documents(state).items()
        if not path.exists() or path.read_text(encoding="utf-8") != expected
    ]


def save(state):
    atomic(STATE, json.dumps(state, indent=2) + "\n")
    for path, text in documents(state).items():
        atomic(path, text)


def evidence_file(relative):
    path = (ROOT / relative).resolve()
    if not path.is_relative_to(ROOT):
        raise ValueError("Evidence must be inside this repository.")
    relative = path.relative_to(ROOT).as_posix()
    if not relative.startswith("docs/evidence/") or path.is_symlink():
        raise ValueError("Use reviewed JSON evidence under docs/evidence/.")
    payload = json.loads(path.read_text(encoding="utf-8"))
    for field in ("scope", "cause", "decision", "result",
                  "regression_test", "limitations"):
        if not isinstance(payload.get(field), str) or not payload[field].strip():
            raise ValueError(f"Evidence requires nonempty string: {field}")
    return relative, hashlib.sha256(path.read_bytes()).hexdigest()


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument("command",
                        choices=["init", "record", "check", "reconcile"])
    parser.add_argument("--task", choices=TASKS)
    parser.add_argument("--kind", choices=sorted(KINDS))
    parser.add_argument("--result", choices=["pass", "fail", "blocked", "pending"])
    parser.add_argument("--by")
    parser.add_argument("--evidence")
    args = parser.parse_args()

    DIRECTORY.mkdir(parents=True, exist_ok=True)
    (ROOT / ".quality").mkdir(exist_ok=True)
    with (ROOT / ".quality/gates.lock").open("w") as lock:
        fcntl.flock(lock, fcntl.LOCK_EX)

        if args.command == "init":
            if STATE.exists():
                raise SystemExit("Ledger exists; refusing to reset evidence.")
            state = {"schema": 1, "events": []}
            for task in ("g1-signin", "g1-revoke", "g1-disable"):
                state["events"].append({
                    "id": f"initial-{task}", "at": now(), "task": task,
                    "kind": "reported", "result": "pass",
                    "by": "user report; not independently reproduced",
                })
            save(state)
            return

        state = json.loads(STATE.read_text(encoding="utf-8"))
        differences = drift(state)

        if args.command == "check":
            if differences:
                raise SystemExit("GATE_DOCUMENT_DRIFT: " + ", ".join(differences))
            print(RESPONSE.read_text(encoding="utf-8"), end="")
            return

        if differences and args.command != "reconcile":
            raise SystemExit(
                "GATE_DOCUMENT_DRIFT: investigate before recording. "
                + ", ".join(differences)
            )

        if not args.by or not args.evidence:
            raise SystemExit("--by and --evidence are required.")

        relative, digest = evidence_file(args.evidence)
        record = {
            "id": f"event-{len(state['events']) + 1:04d}",
            "at": now(), "by": args.by,
            "head": git("rev-parse", "HEAD"),
            "snapshot": snapshot(),
            "evidence": relative, "evidence_sha256": digest,
        }

        if args.command == "record":
            if not args.task or not args.kind or not args.result:
                raise SystemExit("--task, --kind, --result are required.")
            record.update(
                task=args.task, kind=args.kind, result=args.result
            )
        else:
            record.update(kind="reconciliation", result="recorded")
            record["drifted_documents"] = differences

        state["events"].append(record)
        save(state)


if __name__ == "__main__":
    main()