# Browser and accessibility support

Policy target: WCAG 2.2 AA; latest two stable Chrome, Edge, and Firefox versions
plus current Firefox ESR; latest two Safari major versions on macOS and iOS.

The existing UI includes semantic landmarks, heading order, a skip link,
keyboard-visible focus indicators, named links, mobile-safe navigation,
reduced-motion support, non-color-only recovery status, and main-content focus
after internal Inertia navigation. Recovery messages use safe mapped outcome
codes and alert/status semantics; retry is a full-page anchor only when SSO is
ready. Component tests distinguish that anchor from mocked Inertia links and
check representative page accessibility with axe.

Vitest/jsdom component tests and an axe-core smoke check run without a browser. No
TypeScript conversion was introduced; the existing JSX is checked with TypeScript's
`allowJs`/`checkJs` mode. Real-browser behavior and the supported browser matrix
remain unverified. Manual checks still required include responsive reflow and
zoom, keyboard-only navigation, focus/announcement behavior, real Google
entrypoint navigation, recovery, and full-page versus Inertia 404 presentation.
VoiceOver with real Safari and NVDA are also pending.

The requested design-source `index.html` and `styles.css` were not available in
this session. The supplied screenshot is not a substitute for those source
files, so the reference-driven visual refresh and its visual/accessibility
acceptance remain blocked; no claim of matching the reference is made. See the
[live verification record](LIVE_VERIFICATION.md).
