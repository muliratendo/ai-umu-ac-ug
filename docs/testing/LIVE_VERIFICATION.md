# Live verification record

**Record date:** 2026-11-14  
**Classification:** secret-free; user-reported evidence unless noted otherwise

## Evidence supplied

- The user reported that Sail started successfully and the frontend build
  completed. These outcomes were not independently reproduced during this
  correction task.
- The effective local development database was reported as `laravel`, not
  `platform`. This is distinct from PHPUnit's isolated `testing` database.
- SSO readiness checks were reported as true. No credential/configuration values
  are recorded here.
- The browser reached the application's generic access-denied state.
- An approval had been entered for a misspelled domain instead of the intended
  Google account. The personal address is intentionally omitted.
- Navigation sign-in was reported not to work, while direct navigation to
  `/auth/google` did work.
- The supplied screenshot shows the generic denial banner on Home.

## Not established

The supplied evidence does **not** establish a successful login for the
corrected Google identity, live revocation/reactivation, or a reproduction of
the current live 404 behavior. It also does not establish post-change browser
click behavior, screen-reader behavior, or responsive visual acceptance.

No real Google credentials were used by the agent. No live SSO configuration,
approval, identity, session, database, or user record was changed during this
task. Backend Socialite tests are mocks and are not live OAuth evidence.

## Source and follow-up status

The requested attached `index.html` and `styles.css` were not present in the
attachment directory available to this session. Although a screenshot and
written visual direction were supplied, the source-based refresh was not
implemented and must not be considered visually accepted.

Pending evidence: real-browser checks for responsive layout, keyboard behavior,
full-browser Google navigation, recovery states, and full-page versus Inertia
404s; live verification with the corrected test identity; and direct live
evidence for reactivation and the current 404 behavior. Real credentials must
remain operator-controlled and must not be automated or copied into this
record.

## Follow-up completion attempt (later session, 2026-11-14)

The historical note above is retained. A later session re-checked the design
prerequisite before any UI change and made a second attempt to complete the
reference-driven refresh.

**Source inspection result:** neither required file exists. The required paths
`docs/design/references/umu-ai/index.html` and
`docs/design/references/umu-ai/styles.css` are absent; the `docs/design/`
directory does not exist; no `index.html` or `styles.css` is present anywhere in
the repository, in its git history, in the WSL home directory, or in the Windows
attachment/user folders; and the attachment directory referenced by an earlier
request does not exist. Per instruction, the visual implementation was stopped
and the exact missing paths are recorded here. No substitute source, screenshot-
derived implementation, or visual acceptance claim was produced.

**Automated results re-verified in that session** (native WSL PHP 8.4; the
existing `platform-mysql-1` MySQL 8.4 container on `127.0.0.1:3306`; isolated
`testing` database; no volume reset or rebuild):

- Full backend suite with assets present: `40 passed (229 assertions)`, exit 0.
- Full backend suite with `public/build` and `public/hot` moved aside and restored
  through a shell trap: `40 passed (229 assertions)`, exit 0.
- `npm run typecheck` exit 0; `npm run test` 15 passed, exit 0.
- `composer validate --strict` exit 0; `composer check-platform-reqs` exit 0.
- Production Vite build with manifest verification, built to a temporary
  directory so the developer's `public/build` was preserved: exit 0; manifest
  contains the app CSS and JS entries.
- `php artisan route:list --json` exit 0 (12 routes); `git diff --check` exit 0.

**Browser checks not performed:** no browser automation tool was available to
that session and no local application server was running. Therefore the following
remain unverified and are not claimed: desktop and narrow-screen Home/Community,
Account with an isolated test identity, recovery states, full-page and Inertia
404s, keyboard and skip-link focus behavior, and a real full-browser click from
the Google sign-in control. The backend Socialite tests remain mocks and are not
live OAuth evidence. No corrected-account login, live reactivation, or reproduced
live 404 is claimed.

**Repository-state note:** the published commit `e86d5d8` recorded
`apps/platform/artisan`, `infra/local/mysql-init/01-create-testing-database.sh`,
and `tests/infra/mysql-init-regression.sh` at mode `100644`, while the working
tree retains all three at `100755` as an unstaged mode change. The scripts and
the unrelated `artisan` mode change were left untouched and unstaged.

## Design refresh completed (later session, 2026-11-14)

The historical notes above are retained: in the earlier sessions the required
design source files were genuinely absent and no visual work was done.

**Source inspection:** in this session the user's original reference files are
present and were read completely before any UI change —
`docs/design/references/umu-ai/index.html` (265 lines) and
`docs/design/references/umu-ai/styles.css` (1132 lines). Their embedded scripts,
links, and external dependencies were inspected but not executed, served, or
introduced; they are treated as inert design references. The visual refresh was
implemented against these files in the existing React/Inertia components and
application CSS.

**Automated results re-verified in this session** (native WSL PHP 8.4.24; the
existing `platform-mysql-1` MySQL 8.4 container on `127.0.0.1:3306`; isolated
`testing` database; no volume reset or rebuild):

- `php artisan test`: `40 passed (229 assertions)`, exit 0, both with assets
  present and with `public/build` (and absent `public/hot`) moved aside and
  restored through a shell trap.
- `npm run test`: `23 passed`, exit 0 (was 15; added coverage for the redesigned
  pages and truthfulness, real Google anchors, internal Inertia links, POST
  logout buttons, disabled-action visibility, and recovery focus behavior).
- `npm run typecheck`: exit 0.
- `composer validate --strict`: exit 0; `composer check-platform-reqs`: exit 0.
- `npx vite build` to a temporary output directory (the developer's `public/build`
  was preserved): exit 0; the manifest contains the app CSS and JS entries.
- `php artisan route:list --json`: exit 0 (12 routes); `git diff --check`: exit 0.

**Browser checks not performed:** this session again had no browser automation
tool, no browser binary (Chromium/Firefox), and no running local application
server (`127.0.0.1:80` and `127.0.0.1:8000` both unreachable). The following
therefore remain unverified and are not claimed: desktop and narrow-screen
Home/Community, Account with an isolated test identity, recovery states on
full-page load and Inertia transition, full-page and Inertia 404s, keyboard and
skip-link focus behavior, and a real full-browser click from the Google sign-in
control. Because no browser evidence was collected, the refresh is implemented
and component-tested but not yet visually accepted.

**Live-flow status:** backend Socialite tests remain mocks and are not live OAuth
evidence. No corrected-account login, live reactivation, or reproduced live 404
is claimed; those still require operator-controlled live evidence.

**Repository-state note:** the working tree still retains the three unstaged mode
changes noted above (`100755`), left untouched; no dependency manifest or
lockfile changed.

<!-- frontend-release-reliability-2026-11-17 -->
## Frontend navigation incident — user verification

On 17 November 2026 the user confirmed Google-button navigation worked after installing
fresh compiled assets and replacing the active manifest. This establishes user-reported
navigation recovery, NOT callback login, live reactivation, or production acceptance.
New reliability automation is pending execution. See
[incident](incidents/2026-11-17-stale-oauth-frontend.md) and
[release requirements](FRONTEND_RELEASE_RELIABILITY.md).
