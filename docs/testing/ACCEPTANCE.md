# Acceptance checks

From `apps/platform`, run:

```bash
composer validate --strict
composer check-platform-reqs
composer install --no-interaction --prefer-dist --no-progress
npm ci
docker compose up -d --build laravel.test mysql
php artisan test
npm run typecheck
npm run test
npm run build
test -f public/build/manifest.json
php artisan route:list --except-vendor
git diff --check
```

Backend tests use Laravel's `withoutVite` testing facility and cover public responses,
Inertia components, disabled routes, production-safe exception rendering, and a real
MySQL 8.4 insert/read against the distinct `testing` database. Frontend tests cover
community units, accessible navigation and skip links, deferred-feature messaging,
and an axe smoke check. The asset check verifies the generated manifest separately.
CI repeats this workflow with a disposable MySQL service; no CI run is claimed here.
