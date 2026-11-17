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

The reference-driven visual refresh is **implemented**. The source attachments
were unavailable in the original implementation session and in an earlier
follow-up attempt, so the refresh was left blocked then; that history is retained
in the [live verification record](../testing/LIVE_VERIFICATION.md). The user's
original `docs/design/references/umu-ai/index.html` and
`docs/design/references/umu-ai/styles.css` later became available. Both were read
in full as inert design references, their embedded scripts and external links
were inspected but not executed, served, or introduced, and their visual language
was translated into the existing React/Inertia components and application CSS:
warm paper and cream surfaces, dark ink sections and text, teal emphasis,
restrained orange/yellow accents, a constrained content width, editorial
typography, thin borders, numbered cards, and field-note/orbit decoration on
responsive grids. The shared header, navigation, footer, and page shell, plus
Home, Community, Account, authentication recovery, and NotFound were refreshed
while preserving the four community units, the broader UMU AI Community identity,
public Home/Community access, the minimal invite-only Account page, and the
truthful implemented-versus-planned wording. No reference script, CDN resource,
remote font, new package, or component framework was added. Orange/teal and
area-card accent colours were darkened after measuring WCAG contrast so button and
label pairings reach 4.5:1. Browser acceptance for the refreshed layout remains
outstanding because no browser automation tool and no running local server were
available, so visual acceptance is not claimed yet.

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

Next authorized task: completion and acceptance of this authentication-correction
and design-refresh milestone. The authentication corrections are published
(commit `e86d5d8`) and re-verified by the automated suites. The reference-driven
visual refresh is now implemented against the user's supplied design sources and
its automated component/accessibility checks pass, but real-browser acceptance
remains outstanding, so the milestone is not yet fully accepted. Explicit
administrative authorization and Twill enablement (roadmap stage 2) follow only
after that acceptance and are not started by this milestone.

<!-- frontend-release-reliability-2026-11-17 -->
## Frontend release-reliability correction

User verified local Google-button navigation recovery after refreshing served assets.
Inertia-aware initiation, manifest validation and built-asset smoke tooling are supplied;
execution evidence is required before acceptance. No production rollout or administrative
milestone is implied. See [requirements](../testing/FRONTEND_RELEASE_RELIABILITY.md).
