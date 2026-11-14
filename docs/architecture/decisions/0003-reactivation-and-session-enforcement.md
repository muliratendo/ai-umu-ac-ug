# ADR 0003: explicit SSO reactivation and request-time enforcement

**Date:** 2026-11-14  
**Status:** accepted for this bounded correction milestone  
**Authority:** user-approved scope; technical details selected by the agent

## Decision

Separate first-time approval, revocation, and reactivation:

- `sso:approve {email}` creates or updates only an unredeemed approval. It
  refuses a redeemed invitation, including one whose revocation was cleared
  manually.
- `sso:revoke {email}` disables new login and protected access.
- `sso:reactivate {email}` is an explicit local operator command. It requires
  interactive confirmation; a non-interactive caller must pass `--force`.
  Only an operator independently authorized to manage approvals may use it.
  Shell access alone does not establish university approval, and the application
  does not create a role, administrator account, or approval authority.

The reactivation transaction locks the invitation and its redeemed user and
Google identity, and validates an existing approved/redeemed record with
consistent user email, invitation email, identity email, provider, and a
non-empty stable subject. It clears only `revoked_at` and creates a minimal
event retaining the prior revocation timestamp and an optional short reason.
IDs, `approved_at`, `redeemed_at`, `redeemed_by`, user and identity rows,
provider, subject, and email bindings remain unchanged. No account is created,
linked by email, or granted privileges. A consistent active record is an
idempotent no-op. Missing, unredeemed, malformed, or inconsistent records fail
without mutation. Existing unique constraints remain the final binding
constraints.

The transaction and row locks serialize conflicting writes to the same
invitation/binding. Login and reactivation lock related rows in different
orders in some paths, so the transaction retry is retained for deadlock
handling. Regression tests exercise sequential state transitions only; they do
not claim to prove simultaneous concurrency behavior.

Protected SSO access uses one `EnsureSsoAccess` middleware boundary. On the next
protected request, disabled SSO, incomplete required configuration, or a
revoked/ineligible binding ends the current login, invalidates its session,
regenerates the CSRF token, and returns a safe recovery outcome. This is
request-time enforcement, not proactive deletion of every stored session when a
configuration flag changes. After re-enablement the invalidated session must
perform a new login. POST logout remains available while SSO is disabled and
public pages remain available.

Fresh Google navigation uses a normal browser anchor, stateful Socialite flow,
minimal scopes, the fixed callback, and Google account-selection prompting.
Application errors map to safe outcome codes. Eligibility denial, revocation,
and account-linking conflicts share generic public messaging; provider and
database-operation failures map to temporary unavailability and are never
rendered raw.

Unknown browser HTML GETs and intentionally unavailable SSO GETs render the
NotFound Inertia page with status 404. Inertia requests keep the page payload,
JSON/API requests keep JSON 404s, method mismatches keep 405, and other status
codes are not normalized to 404. If frontend assets prevent the Inertia error
page from rendering, a fixed safe HTML response is used.

## Rationale

The provider subject is the durable identity key; changing it during recovery
would silently transfer an existing approval. Preserving the binding while
recording prior revocation evidence makes operator action explicit and
recoverable. Request-time checks enforce feature disablement without claiming
instantaneous session deletion. A single safe outcome vocabulary makes recovery
actionable without disclosing approval or account existence. Exception-based
404 handling avoids a catch-all route that could shadow vendor routes or
convert method mismatches.

## Consequences

- Reactivation restores eligibility for a future login only; it neither signs
  the user in nor resurrects an invalidated session.
- The event records the prior revocation timestamp and operator-supplied reason,
  but no actor identity. The trusted operator and approval authority remain
  external operational controls, not a new authorization system.
- Locking and database unique constraints protect sequential mutation paths;
  simultaneous concurrency has not been demonstrated by the regression suite.
- Live OAuth, real-browser behavior, the corrected account, reactivation, and
  current live 404 behavior remain subject to the evidence recorded in
  [LIVE_VERIFICATION.md](../../testing/LIVE_VERIFICATION.md).

## Visual reference provenance and limitation

The user supplied a screenshot and textual design direction (including ink,
paper, teal, orange, yellow, constrained width, restrained editorial typography,
thin borders, and responsive layouts) and required reading attached
`index.html` and `styles.css`. Those source files were not available to the
session. No replacement source was sought, no page CSS was inferred from the
screenshot, and no reference-driven visual change is claimed. The visual
refresh remains blocked until the actual source attachments are available.
