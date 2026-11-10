# Threat model

Primary risks are accidental public administration, unsafe OAuth account linking,
secret exposure, and future prompt-injection or data-exfiltration paths. Current
mitigations are no auth routes, no Twill routes, no AI endpoint, no credentials,
safe JSON health output, and a narrow public route inventory.

Before launch, review CSRF/session settings, trusted proxies, authorization policy,
provider outage/replay behavior, object-storage boundaries, rate limits, logging,
and dependency advisories.

The local MySQL test database is explicitly named `testing`, uses the `sail_test`
credential, and is guarded before Laravel's refresh/migration trait runs. CI creates
the same disposable database and credential; neither test path is permitted to fall
back to SQLite.
