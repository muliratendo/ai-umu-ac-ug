# Local development

Commands below use the repository root for Git and `apps/platform` for Laravel.

```bash
cd ~/projects/ai-umu-ac-ug/apps/platform
cp .env.example .env
php artisan key:generate
docker compose up -d --build laravel.test mysql
docker compose exec laravel.test php artisan migrate
npm run dev
```

The committed `docker-compose.yml` is canonical. It builds the Laravel Sail PHP 8.4
runtime, runs the development-only `artisan serve` process, and starts MySQL 8.4
with a named `sail-mysql` volume. The application container reaches MySQL at
`mysql:3306`; host commands reach it at `127.0.0.1:${FORWARD_DB_PORT:-3306}`.
The database healthcheck must be healthy before migrations. The init script creates
the distinct `testing` database and restricted `sail_test` user on first volume
initialization.

Local-only example credentials are in `.env.example`; replace them for any shared
environment. Do not copy a real `.env` into the repository. Local debug is enabled
for development only; production must supply `APP_DEBUG=false` and external secrets.

Run backend tests with `php artisan test`; they use MySQL 8.4 and the isolated
`testing` database. Run `npm run typecheck`, `npm run test`, and `npm run build`
separately. Backend tests bypass Vite asset resolution; the asset check verifies the
generated production manifest. Stop with `docker compose stop`; do not use
`docker compose down -v` as routine cleanup. Use `docker compose logs` for logs.

Google credentials are not required for this foundation. Local callback registration
must never point at the production origin.
