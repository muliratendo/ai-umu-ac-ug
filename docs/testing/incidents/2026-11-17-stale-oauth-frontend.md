# Local incident: stale compiled frontend intercepted Google initiation

Date: 17 November 2026. Environment: local Sail application.
Status: navigation recovery USER-VERIFIED; reliability automation acceptance PENDING.

## Evidence and cause

The React source used a normal anchor for /auth/google. No public/hot file existed.
The active manifest referenced app-DSUUOpau.js. A fresh build produced app-C8m_gTLG.js.
A browser network capture showed an X-Inertia/XMLHttpRequest initiation followed by an
ordinary 302 to Google and a rejected cross-origin OPTIONS preflight. Direct address-bar
navigation worked. Installing fresh assets and changing the manifest resolved the click
failure, as explicitly confirmed by the user in this conversation.

The supported diagnosis is source/served-build drift. The exact historical command or
process that left the old build active was not conclusively identified. Earlier builds
were deliberately staged separately; verifying a build is not promoting/serving it.
Do not blame Google configuration, browser security, MySQL, or CORS middleware.

## Recovery

A new build was validated, the existing build backed up, new hashed assets copied, and
manifest replaced last. Old assets were retained. No OAuth security control was relaxed.

## Acceptance boundaries

The user verified button navigation recovery. This does not establish successful callback
login, live reactivation, screen-reader acceptance, or production readiness. No full HAR,
OAuth state/code, session cookie, or personal test address belongs in this record.

## Follow-up

- Inertia-aware server fallback with regressions.
- Manifest graph integrity and negative tests.
- Local built-asset smoke workflow with rollback and explicit build identity.
- One immutable, tested application+asset artifact per future production release.

Generated automation must be run and its actual SHA/results recorded before closing these
follow-ups. See ../FRONTEND_RELEASE_RELIABILITY.md.
