# Acceptance checks

Run normal application checks from WSL Ubuntu in `~/projects/ai-umu-ac-ug/apps/platform`.
Backend PHPUnit is host-only: PHPUnit pins `127.0.0.1:3306`, so MySQL must be
published on host port 3306. Do not run `php artisan test` inside `laravel.test`.

```bash
composer validate --strict
composer check-platform-reqs
composer install --no-interaction --prefer-dist --no-progress
npm ci
docker compose config -q
npm run typecheck
npm run test
php artisan test
npm run build
test -f public/build/manifest.json
git diff --check
```

The account-reset regression is **disposable-database-only verification**. Do not
run it against persistent development MySQL or an application volume. Run it only
inside a fresh, uniquely named MySQL 8.4 container with no existing named volume;
it deliberately drops and recreates `sail_test` and changes its password. The CI
workflow runs this regression, then explicitly hands `sail_test` back to the
PHPUnit credential (`password`) and verifies the same host, port, user, and
`SELECT DATABASE()` connection before PHP tests.

For normal local integration tests, start MySQL 8.4 and the development container
only after checking the project identity, published port, named volume, and allowed
target:

```bash
docker compose up -d --build laravel.test mysql
docker compose ps
docker compose port mysql 3306
```

The backend tests use the isolated `testing` database and fail closed if effective
Laravel configuration, a connection URL, or the actual connected database identity
is unexpected. Public responses and Inertia components are tested with Vite output
stubbed while the Inertia middleware remains active. Run the backend suite once with
`public/build` present and once with that directory safely moved aside and restored
with a shell trap; never delete existing build artifacts.

The initialization regression first checks unsafe targets without SQL, then executes
real initialization SQL and verifies ordinary and SQL-significant password login,
access to `testing`, and denial of the separate `platform` database. Keep this
separate from normal application tests. The frontend tests cover community units,
accessible navigation and skip links, deferred-feature messaging, and an axe smoke
check. No CI run is claimed here without an actual recorded CI result.