# Implementation status

Implemented: public `/`, `/community`, `/health`, fallback 404, Inertia React
layout, skip link, keyboard-visible focus, reduced-motion support, four
community units, the disabled AI/SSO configuration contracts, isolated MySQL
integration coverage, JSX typechecking, frontend component/accessibility smoke
tests, CI configuration, and **Google SSO with invite-only basic account
access** (roadmap stage 1).

Google SSO details: disabled-by-default feature flag, configuration readiness
checks, Google identity keyed by provider + stable subject, invite-only
approval/revocation via Artisan commands (`sso:approve`, `sso:revoke`),
Socialite stateful state validation, cancellation handling, session
regeneration on login, POST-only local logout, minimal scopes, no token
persistence, minimal protected `/account` page, and no open redirect.  See
ADR 0002 and the [decision register](../decisions/REGISTER.md).

Deferred: explicit administrative authorization and Twill enablement (stage 2),
membership, news/events/newsletter workflows, AI integration, uploads,
production deployment, DNS, cloud resources, and OAuth registration.  No
local-password or public-registration routes are exposed.

Backend tests preserve the Inertia middleware and stub only Vite output, and
are supported from WSL Ubuntu on the host with MySQL published at
`127.0.0.1:3306`.  They verify routing, Inertia components, SSO flow (with
mocked provider), state validation, eligibility, redemption, revocation,
session/logout behavior, and secret-disclosure boundaries without requiring
`public/build`; the separate frontend build verifies the production manifest.
The database guard checks effective Laravel configuration and actual connected
identity before `RefreshDatabase` can migrate or reset.  Browser,
VoiceOver/NVDA, CI execution, Render, and Aiven remain unverified here.

Next authorized task: explicit administrative authorization, then safely enable
Twill (roadmap stage 2).
