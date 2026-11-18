# Gate checklist

## Current gates

| Gate | Work | Status |
|---|---|---|
| 1 | Complete current SSO and CI verification | IN_PROGRESS |
| 2 | Approve separate administrative grants, grant/revoke authority, publishing and audit policy | AWAITING_APPROVAL |
| 3 | Test protected reads and writes with Twill disabled | BLOCKED |
| 4 | Enable and test Twill locally only | BLOCKED |
| 5 | Deliver the draft, publish and unpublish news workflow | BLOCKED |
| 6 | Separately approve and implement leadership content | BLOCKED |
| 7 | Integrate Manus; then complete browser and accessibility acceptance | BLOCKED |
| 8 | Verify security safeguards, monitoring and rollback | BLOCKED |

`g1-browser` retains its historical ID; it now belongs to Gate 7. Final acceptance follows Manus integration. Rescheduling is not a pass.

## Tasks

- [ ] Gate 1: `g1-ci` — IN_PROGRESS; requires positive, negative, regression, decision.
- [ ] Gate 1: `g1-signin` — REPORTED_PASS; requires positive, negative, regression, decision.
- [ ] Gate 1: `g1-revoke` — REPORTED_PASS; requires positive, negative, regression, decision.
- [ ] Gate 1: `g1-disable` — REPORTED_PASS; requires positive, negative, regression, decision.
- [ ] Gate 2: `g2-policy` — PENDING; requires approval, negative, decision.
- [ ] Gate 3: `g3-authorization` — PENDING; requires positive, negative, regression, decision.
- [ ] Gate 4: `g4-twill` — PENDING; requires positive, negative, regression, decision.
- [ ] Gate 5: `g5-news` — PENDING; requires positive, negative, regression, decision.
- [ ] Gate 6: `g6-approval` — PENDING; requires approval, decision.
- [ ] Gate 6: `g6-leadership` — PENDING; requires positive, negative, regression, decision.
- [ ] Gate 7: `g7-designs` — PENDING; requires positive, negative, regression, decision.
- [ ] Gate 7: `g1-browser` — REVALIDATION_REQUIRED; requires positive, negative, regression, decision, manual.
- [ ] Gate 8: `g8-ddos-cache` — PENDING; requires positive, negative, regression, decision, manual.
- [ ] Gate 8: `g8-mfa-privileges` — PENDING; requires positive, negative, regression, decision, manual.
- [ ] Gate 8: `g8-auth-sessions` — PENDING; requires positive, negative, regression, decision, manual.
- [ ] Gate 8: `g8-capacity` — PENDING; requires positive, negative, regression, decision, manual.
- [ ] Gate 8: `g8-monitoring` — PENDING; requires positive, negative, regression, decision, manual.
- [ ] Gate 8: `g8-rollout-recovery` — PENDING; requires positive, negative, regression, decision, manual.

## Evidence history

- 2026-11-18T10:11:22.311230+00:00 — g1-signin: reported/pass; no evidence path.
- 2026-11-18T10:11:22.312045+00:00 — g1-revoke: reported/pass; no evidence path.
- 2026-11-18T10:11:22.312056+00:00 — g1-disable: reported/pass; no evidence path.
- 2026-11-18T10:15:11.621472+00:00 — g1-ci: negative/pass; docs/evidence/ci-preflight-negative-tests.json.
- 2026-11-18T10:19:52.243926+00:00 — g1-browser: schedule_migration/pending; Moved Gate 1 → Gate 7 after Manus; legacy evidence preserved..
- 2026-11-18T20:09:58.994365+00:00 — g1-ci: positive/pass; docs/evidence/ci-positive-e2ca1ef.json.
