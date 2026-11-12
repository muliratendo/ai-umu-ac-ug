# Implementation status

Implemented: public `/`, `/community`, `/health`, fallback 404, Inertia React layout,
skip link, keyboard-visible focus, reduced-motion support, four community units, the
disabled AI/SSO configuration contracts, isolated MySQL integration coverage, JSX
typechecking, frontend component/accessibility smoke tests, and CI configuration.

Deferred: Google SSO, membership, news/events/newsletter workflows, Twill routes and
authorization, AI integration, uploads, production deployment, DNS, cloud resources,
and OAuth registration. No local-password or public-registration routes are exposed.

Backend tests preserve the Inertia middleware and stub only Vite output, and are
supported from WSL Ubuntu on the host with MySQL published at `127.0.0.1:3306`.
They verify routing and Inertia components without requiring `public/build`; the
separate frontend build verifies the production manifest. The database guard checks
effective Laravel configuration and actual connected identity before
`RefreshDatabase` can migrate or reset. Browser, VoiceOver/NVDA, CI execution,
Render, and Aiven remain unverified here.

Next authorized task: implement and review the Google SSO boundary after university
identity and role policy are approved.