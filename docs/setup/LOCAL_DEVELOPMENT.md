# Local development

This project is developed from **WSL Ubuntu** on the Linux filesystem. Use the
repository at `~/projects/ai-umu-ac-ug`; do not run these commands from a Windows
checkout or a Windows Node/PHP installation.

## Prerequisites

From WSL Ubuntu, verify PHP 8.4+, Composer, Node/npm, Docker Desktop with WSL
integration, and Docker Compose are available:

```bash
cd ~/projects/ai-umu-ac-ug
php -v
composer -V
node --version
npm --version
docker compose version
cd apps/platform
```

Install the locked dependencies before running any Artisan command:

```bash
composer install --no-interaction --prefer-dist --no-progress
npm ci
```

Initialize the environment only when `.env` is absent. Never overwrite an existing
`.env`, and generate an application key only for that newly initialized environment:

```bash
if [ ! -f .env ]; then
    cp .env.example .env
    php artisan key:generate
fi
```

The committed `docker-compose.yml` is canonical. It builds the Laravel Sail PHP 8.4
runtime, runs the development-only `artisan serve` process, and starts MySQL 8.4
with a named `sail-mysql` volume:

```bash
docker compose up -d --build laravel.test mysql
```

The application container reaches MySQL at `mysql:3306`. Host commands from WSL
reach it at `127.0.0.1:3306`; PHPUnit deliberately pins this host target, so the
published MySQL port must be 3306 and available. Do not advertise or run backend
PHPUnit tests inside `laravel.test`: `127.0.0.1` there is the application container,
not the MySQL service. The database healthcheck must be healthy before migrations.

## Host and container commands

Run host-only development and test commands from WSL Ubuntu in `apps/platform`:

```bash
php artisan test
npm run typecheck
npm run test
npm run build
test -f public/build/manifest.json
```

These host tests use MySQL 8.4 and the isolated `testing` database. They preserve
the Inertia middleware and stub only Vite asset output, so public responses and
Inertia component assertions work with or without `public/build`. The build command
is separate and verifies the production manifest.

Use container commands only for the development runtime and development database:

```bash
docker compose exec laravel.test php artisan migrate
# Other Artisan commands are likewise prefixed with docker compose exec laravel.test.
```

The init script creates the distinct `testing` database and restricted `sail_test`
user **only when MySQL initializes a fresh volume**. It is idempotent, but Docker
will not rerun initialization scripts for an existing volume. To initialize an
existing volume without deleting or reinitializing its data, first verify the
project, service, published port, and volume with `docker compose ps`,
`docker compose port mysql 3306`, and `docker volume ls`, then run explicitly:

```bash
docker compose exec -T mysql /docker-entrypoint-initdb.d/01-create-testing-database.sh
```

Do not use `docker compose down -v` as routine cleanup. Stop with
`docker compose stop`; use `docker compose logs` for diagnostics. Never copy a real
`.env` into the repository or share local credentials.

Local-only example credentials are in `.env.example`; replace them for any shared
environment. Google credentials are not required for this foundation, and local
callback registration must never point at the production origin.