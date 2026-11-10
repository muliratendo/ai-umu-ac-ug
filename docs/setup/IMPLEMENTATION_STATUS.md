# Implementation status

Implemented: public `/`, `/community`, `/health`, fallback 404, Inertia React layout,
skip link, keyboard-visible focus, reduced-motion support, four community units, the
disabled AI/SSO configuration contracts, isolated MySQL integration coverage, JSX
typechecking, frontend component/accessibility smoke tests, and CI configuration.

Deferred: Google SSO, membership, news/events/newsletter workflows, Twill routes and
authorization, AI integration, uploads, production deployment, DNS, cloud resources,
and OAuth registration. No local-password or public-registration routes are exposed.

Backend tests verify routing without a built asset directory; `npm run build` verifies
the production frontend manifest separately. Browser, VoiceOver/NVDA, CI execution,
Render, and Aiven remain unverified here.

Next authorized task: implement and review the Google SSO boundary after university
identity and role policy are approved.
