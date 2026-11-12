# Architecture overview

This is one Laravel modular monolith under `apps/platform`, with an Inertia 3 and
React 19 public frontend.  Database-backed sessions and queues are the intended
defaults; no Redis or microservices are introduced.  Twill is an in-process future
editorial boundary, not a separate service.

Public origin: `https://ai.umu.ac.ug`.  Active public routes: `/`, `/community`,
and `/health`.  A safe 404 fallback handles unmatched GET requests.

Google SSO routes (`/auth/google`, `/auth/google/callback`, `/account`,
`/logout`) are registered but gated by a disabled-by-default feature flag and
configuration readiness checks.  When SSO is disabled or incompletely
configured, the SSO routes return 404 and no sign-in UI is rendered.  See ADR
0002 and the [decision register](../decisions/REGISTER.md).

Reserved or deferred: `/news`, `/events`, `/dashboard`, `/admin`, and `/ai`.
Twill routes remain disabled; no admin or AI endpoints are registered.
