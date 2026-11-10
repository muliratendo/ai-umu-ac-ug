# Local development

Commands below use the repository root for Git and `apps/platform` for Laravel.

```bash
cd ~/projects/ai-umu-ac-ug/apps/platform
cp .env.example .env
php artisan key:generate
./vendor/bin/sail up -d
./vendor/bin/sail artisan migrate
npm run dev
```

The Compose database must be MySQL 8.4 and use a named volume. Use a separate
testing database (`testing`) and set `DB_DATABASE` only in the test environment.
Run `./vendor/bin/sail test` and `npm run build`. Stop with `./vendor/bin/sail stop`;
do not use `down -v` as routine cleanup. Check `./vendor/bin/sail logs` for startup
and use the documented health/readiness check before migrations.

Google credentials are not required for this foundation. Local callback registration
must never point at the production origin.
