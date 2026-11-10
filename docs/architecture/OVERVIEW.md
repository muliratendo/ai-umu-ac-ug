# Architecture overview

This is one Laravel modular monolith under `apps/platform`, with an Inertia 3 and
React 19 public frontend. Database-backed sessions and queues are the intended
defaults; no Redis or microservices are introduced. Twill is an in-process future
editorial boundary, not a separate service.

Public origin: `https://ai.umu.ac.ug`. `/`, `/community`, and `/health` are active.
`/news`, `/events`, `/auth/google`, `/dashboard`, `/admin`, and `/ai` are reserved
or deferred and are not registered by this foundation.
