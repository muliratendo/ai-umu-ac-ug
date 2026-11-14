# Implementation status

Implemented: public `/`, `/community`, `/health`, deterministic 404, Inertia React
layout, skip link, keyboard-visible focus, reduced-motion support, four
community units, the disabled AI/SSO configuration contracts, isolated MySQL
integration coverage, JSX typechecking, frontend component/accessibility smoke
tests, CI configuration, and **Google SSO with invite-only basic account
access** (roadmap stage 1).

Google SSO details: disabled-by-default feature flag and readiness checks;
identity keyed by provider + stable subject; separate first-time approval,
revocation, and confirmed local reactivation commands; stateful Socialite flow;
safe recovery outcomes; request-time SSO enforcement on protected access;
session regeneration on login; POST-only local logout; minimal scopes and no
token persistence; minimal protected `/account`; and no open redirect.
Reactivation preserves the original identity binding and records prior
revocation evidence. See ADR 0002, [ADR 0003](../architecture/decisions/0003-reactivation-and-session-enforcement.md),
and the [decision register](../decisions/REGISTER.md).

The reference-driven visual refresh is **not implemented**. The user supplied a
screenshot and a request to read `index.html` and `styles.css`, but those source
attachments were unavailable in this session. No replacement reference was
searched for and no visual implementation or acceptance claim is inferred from
the screenshot. See the [live verification record](../testing/LIVE_VERIFICATION.md).

Deferred: explicit administrative authorization and Twill enablement (stage 2),
membership, news/events/newsletter workflows, AI integration, uploads,
production deployment, DNS, cloud resources, and OAuth registration.  No
local-password or public-registration routes are exposed.

Backend tests preserve the Inertia middleware and stub only Vite output, and
are supported from WSL Ubuntu on the host with MySQL published at
`127.0.0.1:3306`.  They verify routing, Inertia components, SSO flow (with
mocked provider), state validation, eligibility, redemption, revocation,
reactivation binding/history, request-time session/logout behavior, recovery,
404 negotiation, and secret-disclosure boundaries without requiring
`public/build`; the separate frontend build verifies the production manifest.
The database guard checks effective Laravel configuration and actual connected
identity before `RefreshDatabase` can migrate or reset. Browser, live OAuth
correction/reactivation, current 404 reproduction, VoiceOver/NVDA, CI
execution, Render, and Aiven remain unverified unless recorded otherwise in the
live-verification record.

Next authorized task: explicit administrative authorization, then safely enable
Twill (roadmap stage 2).
