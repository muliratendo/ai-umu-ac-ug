# Frontend release reliability — requirements and local verification

Status: implementation supplied; execution results pending operator verification.
This document does not authorize deployment or Twill enablement.

## Independent safeguards

1. Correct anchor for Google initiation.
2. Server fallback: an Inertia initiation receives 409/X-Inertia-Location, while a
   document request retains 302. Both generate provider state once and retain readiness.
3. Validate every manifest entry file, CSS/asset file, imports and dynamicImports graph
   reference. Reject missing/empty files, unsafe paths and symlinks.
4. Build once, record identity, promote the same bytes, then browser-test served output.
5. Preserve previous assets/manifest for rollback; detect incorrect content at hashed URLs.

## Local procedure

From apps/platform, after installing the kit and running its PHP tests:

    node --test tools/release/validate-build.test.mjs
    python3 tools/release/local-release.py --sso enabled

Use enabled only in an explicitly approved local Google test window. Use disabled when
SSO is actually unavailable; do not silently toggle flags to satisfy the script.
The script checks the running Sail app is local, checks readiness and /health, takes an
exclusive local workflow lock, runs frontend checks, builds in a private staging directory,
checks source stability, writes identity, backs up old build, copies assets, switches
metadata, and runs browser smoke. It never installs dependencies, starts containers, edits
.env, migrates a DB, commits, pushes, or changes SSO. On a caught failure/interrupt after
switch, it restores the previous manifest and identity, retaining old and unused new assets.

SIGKILL/power loss cannot execute cleanup. Manual recovery: copy previous-build/manifest.json
from the printed private run directory back to apps/platform/public/build/manifest.json,
and restore previous release-identity.json if present (otherwise remove ONLY the newly
installed identity after confirming it was absent before). Do not delete old assets.
Backend source is NOT rolled back by the frontend workflow. Review the controller change
separately. Do not run simultaneous npm/build/Vite processes or edit source during the run.

## Identity and limitations

release-identity.json contains base Git commit, clean/dirty status, source fingerprint,
manifest hash, referenced-file SHA-256 inventory, random build ID and UTC timestamp.
No credentials, absolute workspace paths or OAuth/session data are stored in that public
identity. A dirty local build is labelled local-acceptance-only. It must not be promoted
as an immutable production commit artifact. Fingerprints establish correspondence and
integrity, NOT cryptographic signing or reproducible-build provenance.

The validator covers references declared by the manifest, not arbitrary runtime-generated
URLs or every CSS url() string. The browser smoke adds served-byte checks and JS/CSS MIME
checks. It is not a general JS dependency vulnerability scanner.

The browser intercepts Google navigation and supplies a local response before sending any
request to Google. It does not use credentials or demonstrate successful live OAuth. The
fallback test exercises its HTTP protocol, not every historical Inertia client version.

## Future production release contract

- One clean commit and locked dependencies produce one application+frontend artifact.
- Run application tests, validator negatives and browser checks against that artifact in
  staging; promote identical bytes. Do not rebuild a different artifact afterward.
- Reject public/hot, incomplete manifests, build failure and mixed application versions.
- Switch traffic to a COMPLETE release/image. The local file-copy script is NOT a
  production blue/green or atomic whole-application deployment mechanism.
- Upload immutable hashed assets first; activate matching application/manifest together.
- All replicas must agree on release identity. Verify served assets, not only /health.
- HTML/personalized responses must not be shared-cache stale. Hashed assets may use long
  immutable caching ONLY if a filename never changes its bytes.
- Preserve older hashed assets at a reachable origin for a defined old-tab/rollback window;
  keeping them only inside a stopped/deleted container is insufficient.
- Retain prior complete artifact and backward-compatible schema migration plan. Revert
  traffic to that complete artifact on failed smoke. Do not blindly reverse DB migrations.
- Keep APP_KEY/session configuration stable across compatible releases; never rotate keys
  as routine deployment cache clearing. Ensure intended Inertia asset versioning remains.
- Redact cookies, OAuth state/code and full authorization URLs from logs and test artifacts.
- Establish an authorised retention/expiry policy for private local test evidence.

## Mandatory evidence before closing this correction

Record actual tested SHA AND dirty/source identity, command exits, build ID and inventory
hash, PHP fallback regressions, validator negative results, local browser desktop/mobile
results, and rollback exercise if actually performed. PASS is not inferred from file
creation. Skipped/blocked browser installation remains pending, not passed.

Historical user verification: actual Google button navigation was reported working after
fresh-build installation on 17 November 2026. Actual live login/reactivation remains a
separate acceptance item. See incidents/2026-11-17-stale-oauth-frontend.md.

<!-- local-smoke-passed-d629d24b-4347-478a-b300-753dca18eaf7 -->
## Local workflow verification — 17 November 2026

Evidence: operator-supplied terminal output and local workflow report.

- Workflow result: `LOCAL_SMOKE_PASSED`.
- Base commit: `493c28a293da489880128319e2abbd81f906438b`.
- Worktree clean: `False`.
- Build ID: `d629d24b-4347-478a-b300-753dca18eaf7`.
- Manifest SHA-256: `55302eeb6b30bf4adae9fc133b05d384bf5ce04c3e855c1fb0934bbbd4cd1be1`.
- Artifact SHA-256: `07108b20ef3c046793049690325598ed0671fc773a50ae619196d2905b4d29de`.
- JavaScript entry: `assets/app-rMlbYxeE.js`.
- Validator tests: 17 passed.
- Typecheck: passed.
- Frontend component tests: 38 passed.
- Browser smoke: 8 passed across desktop and mobile viewport projects.
- Served manifest outputs matched the candidate bytes.
- The actual Google control used document navigation.
- Stale Inertia versions requested a local reload.
- Matching Inertia versions received the Google external-location response.
- Final source/build identity verification passed.

Harness corrections required before this result:
- Scoped Vitest discovery to the frontend component-test directory.
- Distinguished stale-version reload responses from current-version OAuth
  external-location responses.

Limits: local verification only. Google navigation was intercepted; this does
not establish real callback login or live revocation/reactivation. Browser
viewport coverage is not full accessibility or cross-browser certification.
No deployment, commit, push, or Twill authorization is implied.
