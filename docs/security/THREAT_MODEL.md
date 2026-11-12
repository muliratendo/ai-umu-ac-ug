# Threat model

Primary risks are accidental public administration, unsafe OAuth account
linking, secret exposure, and future prompt-injection or data-exfiltration paths.

## Current mitigations

- SSO is disabled by default with a configuration readiness check; when disabled
  or incompletely configured, no working sign-in path exists and no secrets leak.
- Identity is keyed by provider + subject (not email); a matching local email
  without a Google identity row is never linked automatically.
- Invite-only access via Artisan commands; no public registration.
- State validation (Socialite stateful flow, consumed on use, `hash_equals`);
  `stateless()` is never used.  Cancellation validates state before responding.
- Session regeneration on login; local logout invalidates the session and
  regenerates the CSRF token.
- No open redirect: post-login redirect is `redirect('/account')` only.
- Minimal scopes (`openid`, `profile`, `email`); tokens not persisted or logged.
- No Twill routes, no AI endpoint, no admin routes.  Safe JSON health output.
- Shared Inertia props expose only `auth.user` (name + email), `sso_available`
  (boolean), and flash messages — no secrets or eligibility disclosure.
- Error responses are generic; raw exception messages are never rendered.

## Before launch, review

CSRF/session settings, trusted proxies, rate limits, logging, dependency
advisories, provider outage/replay behavior, and object-storage boundaries.

## Database safety

The local MySQL test database is explicitly named `testing`, uses the
`sail_test` credential, and is guarded after Laravel bootstraps effective
configuration but before `RefreshDatabase` can migrate or reset.  The guard also
checks the actual connected database identity and rejects connection URLs or
unexpected targets.  CI creates the same disposable database and credential;
neither test path is permitted to fall back to SQLite.  No CI run is claimed
without recorded CI evidence.
