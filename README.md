# UMU AI Community

Initial Laravel 13 modular-monolith foundation for the public UMU AI Community site.

The application lives in [`apps/platform`](apps/platform). Local development uses
PHP 8.4, MySQL 8.4, Laravel Sail, React 19.3, and Inertia 3. See
[`docs/setup/LOCAL_DEVELOPMENT.md`](docs/setup/LOCAL_DEVELOPMENT.md).

Implemented: public home, community page, accessible shared layout, safe 404, health
endpoint, isolated MySQL integration coverage, JSX typechecking, component tests,
and CI configuration. Google SSO, Twill administration, membership, newsletter
delivery, and AI are deliberately deferred.
