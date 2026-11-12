# Agent guidance

- Run PHP and Composer commands from `apps/platform`.
- Run npm commands from `apps/platform`; use native WSL tools, not Windows Node.
- Keep public routes limited to the documented foundation.
- Do not add credentials, OAuth callbacks, public registration, AI inference, or
  enabled Twill administration without an explicit authorized task.
- Preserve the security and accessibility boundaries documented under `docs/`.
- Record material technical and non-technical decisions in
  [`docs/decisions/REGISTER.md`](docs/decisions/REGISTER.md), including
  decisions discovered during implementation.  Link detailed narratives to
  ADRs under `docs/architecture/decisions/` or topic docs.
