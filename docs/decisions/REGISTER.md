# Decision register

Every implementation milestone records material technical **and** non-technical
decisions, including decisions discovered during implementation.  Each entry
has a stable ID, date, status, decision, rationale, authority, affected
artifacts, and unresolved questions.  Detailed narratives live in ADRs or topic
docs; this register is compact and links to them.

Legend: **[policy]** user-approved product policy · **[evidenced]** earlier
decision evidenced by repository commits/docs · **[agent]** agent-selected
technical decision within this task · **[proposal]** requires approval before
action.

---

## D-001 — Minimal Laravel/React/Inertia monorepo

**Date:** not recorded (evidenced by repository) · **Status:** accepted ·
**Class:** [evidenced]

One Laravel modular monolith under `apps/platform` with Inertia 3 and React 19.
No microservices or Redis introduced.

**Authority:** repository commits and ADR 0001.
**Affected:** `apps/platform/composer.json`, `docs/architecture/OVERVIEW.md`.

## D-002 — WSL/Linux working directories

**Date:** not recorded (evidenced by repository) · **Status:** accepted ·
**Class:** [evidenced]

Development runs from WSL Ubuntu on the Linux filesystem.  Windows Node/PHP
installations are not used.

**Authority:** `docs/setup/LOCAL_DEVELOPMENT.md`, `AGENTS.md`.
**Affected:** all local development commands.

## D-003 — MySQL 8.4 with isolated testing database

**Date:** not recorded (evidenced by repository) · **Status:** accepted ·
**Class:** [evidenced]

Backend tests use an isolated MySQL 8.4 database named `testing` with the
`sail_test` credential.  A pre-migration guard checks effective Laravel config
and the actual connected database identity.  No SQLite fallback.

**Authority:** `apps/platform/tests/TestCase.php`, `docs/testing/ACCEPTANCE.md`.
**Affected:** `phpunit.xml`, `docker-compose.yml`, CI workflow.

## D-004 — Disabled-by-default feature boundaries

**Date:** not recorded (evidenced by repository) · **Status:** accepted ·
**Class:** [evidenced]

SSO, AI, and Twill are disabled by default.  Each has a flag and readiness
checks.  Routes that depend on a disabled feature fail safely (404) and do not
leak configuration.

**Authority:** `docs/security/AUTHENTICATION.md`, `config/sso.php`.
**Affected:** `GoogleSso::ready()`, route guards.

## D-005 — Public page ownership

**Date:** not recorded (evidenced by repository) · **Status:** accepted ·
**Class:** [evidenced]

`/` and `/community` are public and require no authentication.  A safe 404
fallback and `/health` endpoint are the only other public routes.

**Authority:** `routes/web.php`, `docs/architecture/OVERVIEW.md`.
**Affected:** public route inventory, Inertia pages.

## D-006 — Twill contained and disabled

**Date:** not recorded (evidenced by repository) · **Status:** accepted ·
**Class:** [evidenced]

Twill is installed but package discovery and its routes are disabled
(`dont-discover` in `composer.json`).  No admin routes are registered.

**Authority:** `apps/platform/composer.json`, `docs/architecture/OVERVIEW.md`.
**Affected:** admin route inventory.

## D-007 — Test asset isolation and database safety

**Date:** not recorded (evidenced by repository) · **Status:** accepted ·
**Class:** [evidenced]

Backend tests preserve the Inertia middleware and stub only Vite output via
`withoutAssets()`.  Database integration tests run on isolated MySQL 8.4 with
a pre-migration guard.  No CI run is claimed without recorded evidence.

**Authority:** `apps/platform/tests/TestCase.php`, `docs/testing/ACCEPTANCE.md`.
**Affected:** all backend test classes.

## D-008 — Disposable-only account-reset regression and CI credential handoff

**Date:** not recorded (evidenced by repository) · **Status:** accepted ·
**Class:** [evidenced]

The account-reset regression runs only in a fresh, uniquely named MySQL 8.4
container with no existing named volume.  CI then hands `sail_test` back to the
PHPUnit credential and verifies the connection.

**Authority:** `docs/testing/ACCEPTANCE.md`, CI workflow.
**Affected:** `tests/infra/mysql-init-regression.sh`, CI steps.

## D-009 — Render/Aiven deployment prerequisites

**Date:** not recorded (evidenced by repository) · **Status:** accepted ·
**Class:** [evidenced]

Deployment targets Render (application) and Aiven (managed MySQL).  Production
readiness is pending; no production deployment, DNS, or cloud resource creation
is performed incidentally.

**Authority:** `docs/operations/RENDER_AIVEN.md`.
**Affected:** deployment documentation only.

## D-010 — Housing reuse/provenance restrictions

**Date:** not recorded (evidenced by repository) · **Status:** accepted ·
**Class:** [evidenced]

Housing content has reuse and provenance restrictions documented separately.

**Authority:** `docs/provenance/REUSE_POLICY.md`.
**Affected:** content reuse policy.

## D-011 — Invite-only Google SSO access policy

**Date:** 2026-11-12 · **Status:** accepted · **Class:** [policy]

Eligible accounts are explicitly invited/approved Google accounts.  Public
visitors retain access to home and community.  Authentication confers basic
account access only — not membership, staff, editor, or administrator access.
Privileged access requires separate explicit approval.  Existing accounts are
not linked automatically when emails match.

**Authority:** user-approved product policy (this milestone prompt).
**Affected:** `GoogleAccountService`, `SsoInvitation`, `GoogleIdentity`,
`GoogleAuthController`, `AccountController`, ADR 0002,
`docs/security/AUTHENTICATION.md`.

## D-012 — Identity keyed by provider + subject, not email

**Date:** 2026-11-12 · **Status:** accepted · **Class:** [agent]

The `google_identities` table stores `provider` + `subject` with a unique
constraint.  Repeat logins resolve the identity by subject.  A matching local
email without a Google identity row fails safely.

**Authority:** agent technical decision, consistent with ADR 0002.
**Affected:** `GoogleIdentity` model/migration, `GoogleAccountService`.

## D-013 — Artisan-only approval/revocation mechanism

**Date:** 2026-11-12 · **Status:** accepted · **Class:** [agent]

Approvals are managed by `sso:approve {email}` and `sso:revoke {email}` Artisan
commands only — no public invitation management, email sending, privileged
dashboard, or administrator bootstrap account.

**Authority:** agent technical decision, consistent with product policy.
**Affected:** `SsoApprovalCommand`, `SsoRevokeCommand`, `SsoInvitation`.

## D-014 — Cancellation handling and state validation on all paths

**Date:** 2026-11-12 · **Status:** accepted · **Class:** [agent]

When Google returns `error` (user cancelled), state is validated before
returning a cancellation message — no provider contact.  Socialite's built-in
stateful flow (`pull` + `hash_equals`) handles the main path.  `stateless()`
is never used.  The cancellation path mirrors the state check to prevent CSRF.

**Authority:** agent technical decision.
**Affected:** `GoogleAuthController`.

## D-015 — Local-only post-login redirect (no open redirect)

**Date:** 2026-11-12 · **Status:** accepted · **Class:** [agent]

Successful login redirects to `/account` via `redirect('/account')`, not
`redirect()->intended()`, to eliminate open-redirect risk from a stored
intended URL.

**Authority:** agent security decision.
**Affected:** `GoogleAuthController`.

## D-016 — Socialite promoted to direct dependency

**Date:** 2026-11-12 · **Status:** accepted · **Class:** [agent]

`laravel/socialite` was present in `composer.lock` as a transitive dependency
but not declared in `composer.json`.  Promoted to a direct `require` entry
(`^5.31`) so it is not accidentally removed by a future `composer update`.
Locked at v5.31.0 in `composer.lock`.

**Authority:** agent dependency decision.
**Affected:** `apps/platform/composer.json`, `composer.lock`.

## D-017 — Minimal scopes, no token persistence

**Date:** 2026-11-12 · **Status:** accepted · **Class:** [agent]

Requested scopes are `openid`, `profile`, `email` only.  Access, refresh, and
ID tokens are not persisted to the database.  No authorization codes, tokens,
state values, or provider responses are logged.

**Authority:** agent security decision, consistent with product policy.
**Affected:** `GoogleAuthController`, `GoogleAccountService`.

## D-018 — Roadmap

**Date:** 2026-11-12 · **Status:** accepted · **Class:** [policy]

1. Google SSO and basic account access (this milestone).
2. Explicit administrative authorization, then safely enable Twill.
3. First editorial workflow (news/events).
4. Separately specified membership/research workflows.
5. Newsletter delivery and AI only after requirements are approved.

Production deployment is separately authorized work, not an incidental action.

**Authority:** user-approved product policy (this milestone prompt).
**Affected:** `docs/setup/IMPLEMENTATION_STATUS.md`, all roadmap docs.

---

## Proposals requiring approval

None at this time.  Live OAuth/browser verification is pending external
OAuth client credentials and exact callback registration.  A hosted-domain
restriction is configurable but not applied until a university domain is
supplied.
