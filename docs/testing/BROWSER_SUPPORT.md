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
an earlier session, so the reference-driven refresh was blocked then; the
screenshot was not treated as a substitute. See the
[live verification record](LIVE_VERIFICATION.md).

A later session re-checked the required paths
`docs/design/references/umu-ai/index.html` and
`docs/design/references/umu-ai/styles.css`. Both are now present and were read in
full as inert references, and the visual refresh was implemented against them in
the existing React/Inertia components and application CSS. Contrast was measured
rather than assumed: orange/teal and area-card accent colours were darkened so
text and button pairings reach 4.5:1, focus indicators use a 3:1 contrast ring,
and the decorative orbit is hidden from assistive technology. The component tests
continue to cover landmarks, the skip link, keyboard-visible focus, reduced
motion, and alert/status recovery semantics, but they are not browser evidence.
That session had no browser automation tool, no browser binary, and no running
local application server, so the desktop, narrow-screen, keyboard, recovery, and
full-page-versus-Inertia-404 browser checks listed above as still required were
not performed and are not claimed.
