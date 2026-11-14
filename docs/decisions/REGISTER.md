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

## D-019 — Authentication recovery correction milestone

**Date:** 2026-11-14 · **Status:** accepted · **Class:** [policy] + [agent]

The user authorized explicit identity-preserving reactivation, full-browser
Google entrypoints, request-time SSO enforcement for protected sessions,
actionable non-disclosing recovery, deterministic browser/Inertia/JSON 404s,
regression coverage, and evidence-based documentation. The user explicitly
excluded later roadmap work, live configuration changes, real credential
automation, and dependency upgrades.

Implementation details selected within that scope: a minimal durable
reactivation-event table; transaction and row locks with the existing unique
constraints; interactive confirmation plus explicit `--force` for automation;
opaque outcome codes; Google `prompt=select_account` on fresh flows; one
request-time middleware boundary; an exception-rendered NotFound response
instead of a catch-all route; and operational/database failures reported as
temporary unavailability rather than eligibility denial. Sequential tests do
not establish simultaneous concurrency behavior. Operator authority remains an
external prerequisite; the command is not an approval system.

**Authority:** product boundaries from the user; technical implementation
choices by the agent.
**Affected:** platform auth, routes, tests, ADR 0003, and security/testing docs.

## D-020 — Missing visual source attachments

**Date:** 2026-11-14 · **Status:** blocked · **Class:** [agent]

The screenshot and requested visual direction were present in the request, but
the required `index.html` and `styles.css` source files were not available in
the attachment directory. Per the user's constraint, no substitute reference
was searched for, no CSS/content was inferred from the screenshot, and the
reference-driven page refresh was not implemented or accepted.

**Authority:** user instruction to stop visual work when those files are
unavailable; attachment availability was verified by the agent.
**Affected:** visual acceptance remains blocked; recorded in the implementation
status, browser support, ADR 0003, and live-verification record.

## D-021 — Avoid unused remote font downloads during builds

**Date:** 2026-11-14 · **Status:** accepted · **Class:** [agent]

The existing Vite configuration requested Instrument Sans from Bunny during
builds, but application source did not reference that font and already uses a
local/system fallback stack. The unused fetch plugin was removed so validation
does not introduce an external font request. This is not a claim that the
reference-driven visual refresh was implemented.

**Authority:** user direction to use CSS/system or already-local fonts and avoid
unnecessary font downloads; the agent verified the font was unused in app
source.
**Affected:** `apps/platform/vite.config.js`; no package or lockfile changes.

## D-022 — Follow-up completion attempt for the design refresh

**Date:** 2026-11-14 · **Status:** superseded by D-023 · **Class:** [agent]

A later session attempted to complete the reference-driven visual refresh but
re-checked the prerequisite first. The required files
`docs/design/references/umu-ai/index.html` and
`docs/design/references/umu-ai/styles.css` are still absent, and the
`docs/design/` directory does not exist. Per the standing instruction, the visual
implementation was stopped, the exact missing paths were reported, and no
substitute source or screenshot-derived implementation was produced. The
authentication corrections published in `e86d5d8` were re-verified unchanged by
the automated suites; browser acceptance checks could not run because no browser
automation tool and no local server were available. The stale "next authorized
task" wording was corrected to point at completion/acceptance of this milestone
rather than automatic Twill enablement.

**Authority:** user instruction to treat the design source files as a hard
prerequisite and to stop when they are missing; prerequisite availability was
verified by the agent across the repository, its history, and the attachment
locations.
**Affected:** `docs/setup/IMPLEMENTATION_STATUS.md`, `docs/testing/ACCEPTANCE.md`,
`docs/testing/BROWSER_SUPPORT.md`, `docs/testing/LIVE_VERIFICATION.md`, and this
register. Visual acceptance remains blocked.

## D-023 — Reference-driven visual refresh implemented

**Date:** 2026-11-14 · **Status:** accepted · **Class:** [agent]

The user's original design sources `docs/design/references/umu-ai/index.html` and
`docs/design/references/umu-ai/styles.css` became available and were read in full
as inert references (embedded scripts and external links inspected but not
executed or introduced). Their visual language — ink/paper/cream surfaces, teal
emphasis, restrained orange/yellow accents, constrained width, editorial
typography, thin borders, numbered cards, field-note/orbit decoration, and
responsive grids — was translated into the existing React/Inertia components and
application CSS. The shared shell, Home, Community, Account, recovery, and
NotFound were refreshed while preserving the four community units, the broader
UMU AI Community identity, public Home/Community access, the minimal invite-only
Account page, and the truthful implemented-versus-planned wording; exploratory
directions remain explicitly exploratory and every CTA points to an implemented
route or a real section. No reference script, external resource, remote font, new
package, or component framework was introduced. Contrast was measured and the
orange/teal and area-card accents were darkened to reach 4.5:1, with a 3:1 focus
ring and decorative art hidden from assistive technology. The automated component,
accessibility, typecheck, backend, Composer, build, and route checks pass; real
browser acceptance remains outstanding because no browser automation or local
server was available, so the milestone is not yet fully accepted.

**Authority:** user instruction to complete the refresh from the supplied
references; technical translation choices by the agent.
**Affected:** `apps/platform/resources/css/app.css`, the React pages/components,
`apps/platform/tests/frontend/pages.test.jsx`, and the implementation/testing
documentation.

## Proposals requiring approval

No additional scope proposal was made. Pending external/evidence prerequisites:
real-browser acceptance of the refreshed visual layout, corrected live test
identity and OAuth callback verification, and direct evidence for live
reactivation and 404 behavior. A hosted-domain restriction remains unapplied
until an authorized domain is supplied.
