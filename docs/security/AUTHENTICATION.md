# Authentication boundary

Google SSO is a future feature. Only placeholder variables are supported:
`GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, `GOOGLE_REDIRECT_URI`, and
`UNIVERSITY_GOOGLE_HOSTED_DOMAIN`. No university domain is assumed.

The implementation must use state validation, exact registered callbacks, the
provider's stable subject identifier, provider-supported identity verification,
account allowlisting, session regeneration, replay/cancellation/outage handling,
and local logout. The `hd` request value and email suffix must not authorize access
or roles. Privileged roles require independent approval. Tokens must not be logged
or retained unnecessarily.
