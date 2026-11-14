# Authentication boundary

Google SSO is an invite-only, disabled-by-default feature. See
[ADR 0002](../architecture/decisions/0002-invite-only-google-sso.md),
[ADR 0003](../architecture/decisions/0003-reactivation-and-session-enforcement.md),
and the [decision register](../decisions/REGISTER.md).

## Configuration

Environment variables (all empty by default except `SSO_ENABLED=false`):

- `SSO_ENABLED` — master switch; when false, initiation and callback return 404.
- `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET` — OAuth client credentials.
- `GOOGLE_REDIRECT_URI` — must match the registered callback and the
  `auth.google.callback` route.
- `UNIVERSITY_GOOGLE_HOSTED_DOMAIN` — configurable but not applied until a
  domain is supplied; does not authorize access or roles by itself.

## Identity and eligibility

Identity is keyed by the Google provider name plus the stable subject ID
(`sub`), not by email.  The `google_identities` table stores this binding with a
unique constraint on `(provider, subject)`.

Eligibility uses a separate `sso_invitations` table. `sso:approve {email}`
records first-time approval. The first verified Google identity matching that
email redeems the approval atomically and binds itself to the stable subject.
Later logins resolve the bound identity by subject. `sso:revoke {email}` blocks
new login and protected access.

`sso:approve` does not reactivate a redeemed, revoked identity. Explicit
reactivation uses the local Artisan command
`sso:reactivate {email}`. It is for a trusted operator who is independently
authorized to manage approvals; shell access alone is not evidence of
university approval, and this application does not establish a new web role or
name an approver. The command prompts for confirmation. Automated use must pass
`--force` explicitly; non-interactive execution without it is refused.

Reactivation accepts only an existing, approved, revoked, redeemed invitation
whose redeemed user email, Google identity email, provider, and non-empty stable
subject are consistent. Under a transaction and row locks it clears only
`revoked_at`, preserving invitation/user/identity IDs, approval history,
`redeemed_at`, `redeemed_by`, provider, subject, and email bindings. A durable
`sso_reactivation_events` row preserves the prior revocation timestamp and a
short reason. Missing, unredeemed, malformed, or inconsistently bound records
fail without mutation. A consistently bound active record is an idempotent
no-op. Reactivation restores eligibility only for a new login; it does not
create an account, rebind an identity, grant privileges, log anyone in, or
resurrect an invalidated session.

A matching local email without a `google_identities` row is not linked
automatically; sign-in fails safely.  Gmail dot/plus rewriting is not applied.

## Flow guarantees

- State validation uses Socialite's built-in stateful flow (`pull` +
  `hash_equals`, consumed on use).  `stateless()` is never used.
- Cancellation (`error` parameter) validates state before returning a message,
  without contacting the provider.
- Session ID is regenerated on successful login.
- Local logout invalidates the session and regenerates the CSRF token.  It does
  not sign the user out of their Google account.
- `/account` uses one reusable request-time access boundary. On the next
  protected request, disabled SSO, unavailable required configuration, or a
  revoked/ineligible binding logs out the user, invalidates the session,
  regenerates the CSRF token, and redirects with a safe recovery outcome.
  Configuration changes do not proactively delete every stored session at the
  instant a flag changes. A session invalidated by the boundary needs a new
  login after re-enablement. POST `/logout` remains available while SSO is off;
  public pages remain open.
- Post-login redirect is `redirect('/account')` — no open redirect.
- Scopes: `openid`, `profile`, `email` only.  Tokens are not persisted or logged.
- Privileged profile fields are never overwritten from provider metadata.
- Google entrypoints are ordinary full-browser anchors. A fresh flow asks Google
  to present account selection; it remains stateful and uses the fixed callback.
- Recovery exposes only server-controlled outcome codes and generic eligibility
  messaging. It does not reveal invitation, revocation, registration, provider
  error, or account-linking details. Retry is a full-browser navigation offered
  only while SSO is ready. People with an eligibility concern are directed to
  the person who approved access; no contact address or public request form is
  claimed.
- Browser HTML 404s use the branded NotFound page with HTTP 404. Inertia and
  JSON negotiation are preserved, method mismatches remain 405, and a small
  static safe HTML fallback is used if frontend assets cannot render the page.
  Other exception statuses are not converted to 404.

## What authentication does not confer

Authentication grants basic account access only.  It does not confer community
membership, staff status, editor access, or administrator access.  No password
login, public registration, password-reset, Twill admin, or AI routes are
registered.  Twill is installed but package discovery and its routes are
disabled.
