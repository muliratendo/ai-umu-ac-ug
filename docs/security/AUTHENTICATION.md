# Authentication boundary

Google SSO is implemented as an invite-only, disabled-by-default feature.  See
ADR 0002 and the [decision register](../decisions/REGISTER.md) for the full
rationale.

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

Eligibility uses a separate `sso_invitations` table.  An Artisan command
(`sso:approve {email}`) records an exact, lower-cased email.  The first verified
Google identity matching that email redeems the approval atomically and binds
itself to the subject.  Later logins resolve the bound identity by subject.
Revoked approvals block both login and existing protected access.

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
- Post-login redirect is `redirect('/account')` — no open redirect.
- Scopes: `openid`, `profile`, `email` only.  Tokens are not persisted or logged.
- Privileged profile fields are never overwritten from provider metadata.

## What authentication does not confer

Authentication grants basic account access only.  It does not confer community
membership, staff status, editor access, or administrator access.  No password
login, public registration, password-reset, Twill admin, or AI routes are
registered.  Twill is installed but package discovery and its routes are
disabled.
