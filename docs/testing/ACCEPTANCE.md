# Acceptance checks

Run normal application checks from WSL Ubuntu in `~/projects/ai-umu-ac-ug/apps/platform`.
Backend PHPUnit is host-only: PHPUnit pins `127.0.0.1:3306`, so MySQL must be
published on host port 3306. Do not run `php artisan test` inside `laravel.test`.

```bash
composer validate --strict
composer check-platform-reqs
composer install --no-interaction --prefer-dist --no-progress
npm ci
docker compose config -q
npm run typecheck
npm run test
php artisan test
npm run build
test -f public/build/manifest.json
php artisan route:list --json
git diff --check
```

The account-reset regression is **disposable-database-only verification**. Do not
run it against persistent development MySQL or an application volume. Run it only
inside a fresh, uniquely named MySQL 8.4 container with no existing named volume;
it deliberately drops and recreates `sail_test` and changes its password. The CI
workflow runs this regression, then explicitly hands `sail_test` back to the
PHPUnit credential (`password`) and verifies the same host, port, user, and
`SELECT DATABASE()` connection before PHP tests.

For normal local integration tests, start MySQL 8.4 and the development container
only after checking the project identity, published port, named volume, and allowed
target:

```bash
docker compose up -d --build laravel.test mysql
docker compose ps
docker compose port mysql 3306
```

The backend tests use the isolated `testing` database and fail closed if effective
Laravel configuration, a connection URL, or the actual connected database identity
is unexpected. Public responses and Inertia components are tested with Vite output
stubbed while the Inertia middleware remains active. Run the backend suite once
with its current assets present and once with any existing `public/build` and
`public/hot` entries safely moved aside and restored with a shell trap. Never
delete, overwrite, or replace developer assets; stop if the temporary destination
already exists.

The initialization regression first checks unsafe targets without SQL, then executes
real initialization SQL and verifies ordinary and SQL-significant password login,
access to `testing`, and denial of the separate `platform` database. Keep this
separate from normal application tests. The frontend tests cover community
units, accessible navigation and skip links, deferred-feature messaging,
sign-in visibility, full-browser Google entrypoint anchors (distinct from
mocked Inertia links), account display, recovery outcomes and actions, POST
logout button semantics, route-change focus, and axe smoke checks. No CI run is
claimed here without an actual recorded CI result.

## Google SSO acceptance

The PHP suite (`GoogleSsoTest`) mocks the provider with `Socialite::fake` and
the `SocialiteUser::fake` helper.  No live Google credentials or requests are
used in CI.  Covered scenarios:

- Public pages remain anonymous-accessible.
- SSO disabled by default: initiation/callback return 404; sign-in UI hidden.
- Explicitly enabled SSO: initiation redirects to Google with state.
- Valid approved first login creates user + identity + redemption.
- Repeat login uses bound subject, not email matching.
- Unapproved, unverified, and email-collision identities are rejected.
- Invitation cannot be redeemed twice by different subjects.
- No privileged status (role/is_admin) is created on login.
- Missing, invalid, expired/replayed state rejected without provider contact.
- Cancellation and provider failure return safe generic messages.
- Fresh Google navigation requests account selection; retry remains a full-page
  anchor and is only offered while configuration is ready.
- Reactivation tests cover approval/redeem/revoke/deny/reactivate/login for the
  original subject, preserved binding identifiers, denial of a different
  subject, no additional user/identity or privileged status, explicit
  confirmation, non-interactive refusal, refusal cases, idempotency, and
  retained revocation evidence. Sequential tests do not prove simultaneous
  concurrency.
- No open redirect on login (always `/account`).
- Session ID regenerated on login; POST-only logout invalidates session.
- Feature-disable and incomplete-configuration checks invalidate an existing
  session on its next protected request; re-enabling does not restore that
  invalidated session; logout and public pages remain available.
- Unauthenticated `/account` redirects home with actionable recovery.
- Unknown full-page and Inertia GETs use NotFound with HTTP 404; JSON 404s,
  disabled SSO 404s, and method mismatch 405 semantics are checked separately.
- Revoked approval blocks re-login and existing protected access.
- Secrets not disclosed in shared props, health, or redirect URLs.
- Twill/admin/password-registration/AI routes remain unavailable
  (`DisabledRoutesTest`).

State-validation coverage uses the real Socialite provider (no fake) for the
missing/invalid/replay cases, not only a mocked happy path. Real-browser
responsive, keyboard, sign-in click, recovery, and 404 checks are pending; no
real credentials are automated. See the
[live verification record](LIVE_VERIFICATION.md). Supplied evidence does not
establish corrected-account login, live reactivation, or a reproduced live 404.

SSO tests that render an Inertia page (e.g. the authenticated `/account`
response and the home-page secrecy check) must use `withoutAssets()` and pass
without `public/build` or `public/hot`.  Redirect and JSON-only assertions do
not need the helper.
