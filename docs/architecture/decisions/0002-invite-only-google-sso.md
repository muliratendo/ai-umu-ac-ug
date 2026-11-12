# ADR 0002: invite-only Google identity and basic account access

**Date:** 2026-11-12  
**Status:** accepted  
**Authority:** user-approved product policy (this milestone prompt)

## Decision

Implement Google OAuth single sign-on as the sole authentication mechanism.
Sign-in is disabled by default (`SSO_ENABLED=false`) and gated by a configuration
readiness check.  No local-password login, public registration, or password-reset
flow exists.

Identity is keyed by the Google provider name plus the stable subject identifier
(`sub`), not by email.  A `google_identities` table stores the binding with a
unique constraint on `(provider, subject)`.

Eligibility uses a separate `sso_invitations` table.  An Artisan command
(`sso:approve {email}`) records an exact, lower-cased email address as a pending
approval.  The first verified Google identity matching that email redeems the
approval atomically (transaction + row lock) and binds itself to the subject.
Later logins resolve the bound identity by subject, never by a fresh email match.

## Scope

Applies to roadmap stage 1 (Google SSO and basic account access).  Does not
confer community membership, staff status, editor access, or administrator
access.  Privileged roles require a separate, future explicit authorization
milestone.

## Rationale

- Email addresses can change or be reused; the stable subject ID is the durable
  Google identity.
- Keeping identity (who you are), eligibility (whether you may sign in), and
  authorization (what you can do) as distinct concerns prevents accidental
  privilege escalation.
- Invite-only access avoids public registration and its abuse surface while
  remaining simple to operate with Artisan commands.
- Disabled-by-default with readiness checks prevents partial or misconfigured
  deployments from exposing a working sign-in path or leaking secrets.

## Consequences

- An existing local user whose email matches a Google identity but who lacks a
  `google_identities` row is **not** linked automatically; sign-in fails safely
  with a generic support message.
- Gmail dot/plus address rewriting is not applied; the exact normalized email
  must match the invitation.
- Tokens (access, refresh, ID) are not persisted; only the subject binding and
  invitation redemption state are stored.
- Manual account-linking is out of scope for this milestone.
- State validation uses Socialite's built-in stateful flow (`hasInvalidState`
  pulls state from the session with `hash_equals` and consumes it, preventing
  replay).  The `stateless()` bypass is never used.
- Session ID is regenerated on successful login; local logout invalidates the
  session and regenerates the CSRF token without signing the user out of their
  Google account.

## Unresolved questions

- A university hosted domain restriction is configurable (`UNIVERSITY_GOOGLE_HOSTED_DOMAIN`)
  but not applied until a domain is supplied; it does not authorize access or
  roles by itself.
- Live OAuth/browser verification remains pending until OAuth client credentials
  and exact callback registration are supplied externally.
