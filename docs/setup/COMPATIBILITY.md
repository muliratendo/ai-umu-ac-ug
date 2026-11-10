# Compatibility report

Checked 2026-11-10 from WSL using package metadata and official package sources.

| Component | Selected candidate | Evidence and result |
|---|---|---|
| PHP | 8.4.24 | `php -v`; compatible with Laravel 13 and selected packages |
| Laravel | 13.40.0 / `^13.17` lock constraint | Composer lock; stable |
| Inertia Laravel | 3.7.0 | Packagist metadata requires Laravel 11.35–13 and PHP ^8.2; compatible |
| React / React DOM | 19.3.1 | npm registry metadata; stable matching versions |
| Inertia React | 3.11.0 | npm registry metadata; stable major 3 |
| React typings | 19.3.0 | npm registry metadata; matching React 19 |
| Twill | 3.6.0 | Packagist metadata requires Laravel ^9–^13; stable and resolved |
| Sail | Laravel skeleton tooling | Sail metadata supports Illuminate/Laravel 13 |
| Node / npm | 22.23.3 / 10.9.9 | WSL runtime; supported Node LTS line |
| Vite / Laravel Vite plugin | 8.3.7 / 3.2.1 | package lock; asset build is validated separately |
| MySQL | 8.4 LTS | Compose runtime and integration test target |
| Frontend verification | TypeScript 7.0.2, Vitest 5.1.0, jsdom 31.0.0, Testing Library React 16.3.3, axe-core 4.14.0 | Node 22.23.3; compatible and locked |

The resolved graph is recorded in `apps/platform/composer.lock` and
`apps/platform/package-lock.json`. Twill is installed but not enabled or routed.
The Compose application uses Laravel Sail's committed PHP 8.4 runtime Dockerfile,
not the Composer bootstrap image. Provider-specific Render and Aiven details remain
deployment prerequisites.

Sources: [Laravel releases](https://laravel.com/docs/13.x/releases),
[Inertia Laravel package metadata](https://packagist.org/packages/inertiajs/inertia-laravel),
[Twill package metadata](https://packagist.org/packages/area17/twill),
[React npm metadata](https://www.npmjs.com/package/react).
