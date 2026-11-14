# Threat model

Primary risks are accidental public administration, unsafe OAuth account
linking, secret exposure, and future prompt-injection or data-exfiltration paths.

## Current mitigations

- SSO is disabled by default with a configuration readiness check; when disabled
  or incompletely configured, initiation/callback are unavailable and sign-in UI
  is hidden.
- Identity is keyed by provider + subject (not email); a matching local email
  without a Google identity row is never linked automatically.
- Invite-only access via Artisan commands; no public registration.
- State validation (Socialite stateful flow, consumed on use, `hash_equals`);
  `stateless()` is never used.  Cancellation validates state before responding.
- Session regeneration on login; local logout invalidates the session and
  regenerates the CSRF token.
- `EnsureSsoAccess` checks protected SSO access at request time. Disabled or
  incomplete SSO configuration and revoked/ineligible bindings log out the
  current user, invalidate that session and regenerate its CSRF token on the
  next protected request. This is not proactive deletion of every stored
  session at configuration-change time; an invalidated session requires a new
  login after re-enablement. POST logout and public pages remain available.
- Reactivation is a local, confirmed Artisan operation for a trusted operator
  independently authorized to manage approvals; shell access alone does not
  establish that authority. It locks and validates the existing redeemed
  invitation, user, and Google binding in a transaction, clears only
  `revoked_at`, and records the prior revocation timestamp. It does not create a
  user, update a subject/email binding, link by email, grant privileges, or
  restore an invalidated session. Unique database constraints remain in force.
- No open redirect: post-login redirect is `redirect('/account')` only.
- Minimal scopes (`openid`, `profile`, `email`); tokens not persisted or logged.
- No Twill routes, no AI endpoint, no admin routes.  Safe JSON health output.
- Shared Inertia props expose only `auth.user` (name + email), `sso_available`
  (boolean), and a server-controlled authentication outcome code — no secrets,
  provider error, or account-existence disclosure.
- Error responses are generic; raw exception messages are never rendered.
- Full-page browser and Inertia 404s share the branded NotFound page and retain
  status 404; JSON clients retain JSON, method mismatches retain 405, and only
  the 404 case is customized. Static safe HTML is the fallback if frontend
  assets are unavailable.

## Before launch, review

CSRF/session settings, trusted proxies, rate limits, logging, dependency
advisories, provider outage/replay behavior, concurrency testing, and
object-storage boundaries.

## Database safety

The local MySQL test database is explicitly named `testing`, uses the
`sail_test` credential, and is guarded after Laravel bootstraps effective
configuration but before `RefreshDatabase` can migrate or reset.  The guard also
checks the actual connected database identity and rejects connection URLs or
unexpected targets.  CI creates the same disposable database and credential;
neither test path is permitted to fall back to SQLite.  No CI run is claimed
without recorded CI evidence.
