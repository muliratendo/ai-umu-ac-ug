# UMU AI Community

Initial Laravel 13 modular-monolith foundation for the public UMU AI Community site.

The application lives in [`apps/platform`](apps/platform). Local development uses
PHP 8.4, MySQL 8.4, Laravel Sail, React 19.3, and Inertia 3. See
[`docs/setup/LOCAL_DEVELOPMENT.md`](docs/setup/LOCAL_DEVELOPMENT.md).

Implemented: public home, community page, accessible shared layout, safe 404,
health endpoint, isolated MySQL integration coverage, JSX typechecking,
component tests, CI configuration, and **Google SSO with invite-only basic
account access** (roadmap stage 1).  See
[`docs/architecture/decisions/0002-invite-only-google-sso.md`](docs/architecture/decisions/0002-invite-only-google-sso.md)
and the [decision register](docs/decisions/REGISTER.md).

Deferred: explicit administrative authorization and Twill enablement (stage 2),
membership, news/events/newsletter workflows, AI integration, and production
deployment.  No local-password or public-registration routes are exposed.
