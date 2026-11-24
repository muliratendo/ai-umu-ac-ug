# 0004 — Use Ghost for publishing

## Decision

Use Ghost as the publishing system. Laravel/Inertia continues to own the
community site and its invite-only Google SSO. Ghost owns articles, editorial
accounts, publication membership, and newsletters. Neither application shares
sessions, staff privileges, or a user database with the other.

Initially, route `/news` to `/news/` and serve `/news/` and its descendants
through the reverse proxy to Ghost. Route the rest of the site to Laravel.
Configure Ghost's canonical URL to include the `/news/` prefix. Start with
Ghost's own theme and ordinary navigation links. No headless rendering,
account synchronization, or webhook dependency is required for launch.

## Local deployment and acceptance

Run a production-like stack under `ai.umu.test` on a loopback-bound proxy.
Keep application and database upstreams private, with separate database
credentials and persistent Ghost content storage. Use fictional content and
synthetic accounts; do not configure live bulk email credentials locally.

Verify Ghost Admin, publication pages, assets, and redirects through the proxy.
Restart Ghost and verify content persists. Stop Ghost and verify the Laravel
home, community, and account-route behavior; stop Laravel and verify Ghost
still serves the publication. The proxy and host remain shared failure points.

## Gate transition

- Gate 2: retain policy approval for real privileges and add Ghost staff
  ownership, onboarding, offboarding, publishing authority, and subscriber data.
- Gate 3: preserve the existing Laravel test-only authorization evidence.
- Gate 4: replace planned Twill enablement with `g4-ghost-local`. Verify local
  routing, persistence, and failure isolation with new evidence.
- Gate 5: use `g5-ghost-editorial` to verify native draft, preview, publish,
  and unpublish with fictional content. Test applicable membership and
  newsletter flows without sending to real subscribers.

Preserve historical records. Do not transfer a Twill or custom-news pass to a
Ghost task. Develop the local synthetic stack without changing approval status.

## Later subdomain move

If publishing moves to a dedicated subdomain, change Ghost's canonical URL
and redirect each old `/news/...` article URL to its corresponding new URL.
Verify assets, canonical links, feeds, and member sign-in before cutover.
