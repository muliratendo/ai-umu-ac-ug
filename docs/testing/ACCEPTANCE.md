# Acceptance checks

From `apps/platform`, run:

```bash
composer validate --strict
php artisan test
npm run build
php artisan route:list --except-vendor
git diff --check
```

Acceptance covers public page rendering, community units, no password/registration
routes, disabled Twill/AI boundaries, safe health output, and the production asset
build. MySQL-backed tests must use an isolated MySQL 8.4 testing database.
