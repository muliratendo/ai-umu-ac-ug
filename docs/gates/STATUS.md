# Gate checklist

## Current gates

| Gate | Work | Status |
|---|---|---|
| 1 | Complete current SSO and CI verification | COMPLETE |
| 2 | Approve separate administrative grants, grant/revoke authority, publishing and audit policy | AWAITING_APPROVAL |
| 3 | Test protected reads and writes with Twill disabled | COMPLETE |
| 4 | Run Ghost locally under /news/ and verify service isolation | BLOCKED |
| 5 | Verify Ghost editorial, member and test newsletter flows | BLOCKED |
| 6 | Separately approve and implement leadership content | BLOCKED |
| 7 | Integrate Manus; then complete browser and accessibility acceptance | BLOCKED |
| 8 | Verify security safeguards, monitoring and rollback | BLOCKED |

`g1-browser` retains its historical ID; it now belongs to Gate 7. Final acceptance follows Manus integration. Rescheduling is not a pass.

## Tasks

- [x] Gate 1: `g1-ci` — COMPLETE; requires positive, negative, regression, decision.
- [x] Gate 1: `g1-signin` — COMPLETE; requires positive, negative, regression, decision.
- [x] Gate 1: `g1-revoke` — COMPLETE; requires positive, negative, regression, decision.
- [x] Gate 1: `g1-disable` — COMPLETE; requires positive, negative, regression, decision.
- [ ] Gate 2: `g2-policy` — PENDING; requires approval, negative, decision.
- [x] Gate 3: `g3-authorization` — COMPLETE; requires positive, negative, regression, decision.
- [ ] Gate 4: `g4-ghost-local` — PENDING; requires positive, negative, regression, decision.
- [ ] Gate 5: `g5-ghost-editorial` — PENDING; requires positive, negative, regression, decision.
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
- 2026-11-18T20:15:18.067254+00:00 — g1-ci: positive/pass; docs/evidence/ci-positive-e2ca1ef.json.
- 2026-11-18T20:19:38.465676+00:00 — g1-ci: negative/pass; docs/evidence/ci-negative-e2ca1ef.json.
- 2026-11-18T20:19:38.709564+00:00 — g1-ci: regression/pass; docs/evidence/ci-regression-e2ca1ef.json.
- 2026-11-18T20:19:38.957334+00:00 — g1-ci: decision/pass; docs/evidence/ci-decision-e2ca1ef.json.
- 2026-11-18T21:53:01.583497+00:00 — g1-signin: positive/pass; docs/evidence/g1-signin-positive.json.
- 2026-11-18T21:53:02.497230+00:00 — g1-signin: negative/pass; docs/evidence/g1-signin-negative.json.
- 2026-11-18T21:53:04.073385+00:00 — g1-signin: regression/pass; docs/evidence/g1-signin-regression.json.
- 2026-11-18T21:53:05.516671+00:00 — g1-signin: decision/pass; docs/evidence/g1-signin-decision.json.
- 2026-11-18T21:53:06.884845+00:00 — g1-revoke: positive/pass; docs/evidence/g1-revoke-positive.json.
- 2026-11-18T21:53:08.684487+00:00 — g1-revoke: negative/pass; docs/evidence/g1-revoke-negative.json.
- 2026-11-18T21:53:09.781413+00:00 — g1-revoke: regression/pass; docs/evidence/g1-revoke-regression.json.
- 2026-11-18T21:53:11.104759+00:00 — g1-revoke: decision/pass; docs/evidence/g1-revoke-decision.json.
- 2026-11-18T21:53:12.576119+00:00 — g1-disable: positive/pass; docs/evidence/g1-disable-positive.json.
- 2026-11-18T21:53:13.880550+00:00 — g1-disable: negative/pass; docs/evidence/g1-disable-negative.json.
- 2026-11-18T21:53:15.318218+00:00 — g1-disable: regression/pass; docs/evidence/g1-disable-regression.json.
- 2026-11-18T21:53:16.159116+00:00 — g1-disable: decision/pass; docs/evidence/g1-disable-decision.json.
- 2026-11-19T09:22:49.327968+00:00 — g1-ci: positive/pass; docs/evidence/g1-ci-86838e0-positive.json.
- 2026-11-19T09:22:49.856318+00:00 — g1-ci: negative/pass; docs/evidence/g1-ci-86838e0-negative.json.
- 2026-11-19T09:22:50.448017+00:00 — g1-ci: regression/pass; docs/evidence/g1-ci-86838e0-regression.json.
- 2026-11-19T09:22:50.954812+00:00 — g1-ci: decision/pass; docs/evidence/g1-ci-86838e0-decision.json.
- 2026-11-19T09:43:22.884287+00:00 — g3-authorization: positive/pass; docs/evidence/g3-authorization-84a98c3-positive.json.
- 2026-11-19T09:43:23.702331+00:00 — g3-authorization: negative/pass; docs/evidence/g3-authorization-84a98c3-negative.json.
- 2026-11-19T09:43:24.529116+00:00 — g3-authorization: regression/pass; docs/evidence/g3-authorization-84a98c3-regression.json.
- 2026-11-19T09:43:25.357354+00:00 — g3-authorization: decision/pass; docs/evidence/g3-authorization-84a98c3-decision.json.
- 2026-11-24T06:18:37.987788+00:00 — g1-ci: positive/pass; docs/evidence/g1-ci-ghost-6fca1b9-positive.json.
- 2026-11-24T06:18:39.365812+00:00 — g1-ci: negative/pass; docs/evidence/g1-ci-ghost-6fca1b9-negative.json.
- 2026-11-24T06:18:40.741737+00:00 — g1-ci: regression/pass; docs/evidence/g1-ci-ghost-6fca1b9-regression.json.
- 2026-11-24T06:18:42.059384+00:00 — g1-ci: decision/pass; docs/evidence/g1-ci-ghost-6fca1b9-decision.json.
