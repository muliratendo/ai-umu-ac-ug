# Render and Aiven prerequisites

Deployment scaffolding is intentionally pending. Render must use a production
Docker runtime and real web server, non-root execution where practical, external
secrets, HTTPS/trusted-proxy configuration, stdout logs, health checks, and a
graceful release process. The filesystem is not durable storage; object storage is
required before editorial uploads.

Aiven must be verified for the selected MySQL version, region, plan, allowlist, and
certificate-verified TLS procedure. Use least-privilege runtime credentials and
separate migration privileges. No resources, DNS, credentials, or production
migrations were created.

The local Compose `artisan serve` process is development-only and is not a Render
runtime. No Render or Aiven workflow is included in this pass.
