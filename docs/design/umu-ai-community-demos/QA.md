# QA notes

## Checks performed

- Confirmed all 12 required HTML files exist and are non-empty.
- Confirmed every HTML page references the same relative stylesheet: `assets/css/styles.css`.
- Confirmed the three external scripts are non-empty, loaded with `defer`, and separated by function.
- Confirmed no `<style>` blocks, `style=` attributes, inline script blocks, `onclick` attributes, root-relative asset links, external fonts, external framework stylesheets, `fetch()`, OAuth URLs, API calls, cookies, or analytics strings are present.
- Confirmed all pages are self-contained with their own header, main content, and footer and remain readable when JavaScript is disabled.
- Confirmed `demos.html` links to every other HTML page.
- Confirmed the approved simulated sign-in points to `account.html`, recovery links are directly addressable, and the account sign-out button points to `signed-out.html` with a no-JS fallback.
- Confirmed the four community units and the exploratory-only disclaimer are retained.

## Viewport and accessibility coverage

The stylesheet includes responsive rules targeting approximately 360px, 768px, and 1440px layouts, plus reflow rules for long names/email addresses, navigation wrapping, reduced motion, print, and narrow screens. A skip link, semantic header/nav/main/footer, one h1 per page, visible focus outline, `aria-current` on the active navigation item, button labels, non-color state marks, and decorative `aria-hidden` art are included.

The reference application's documented contrast adjustments were carried forward: `--orange-text: #b0512a` is used for small text on light surfaces instead of the lower-contrast decorative orange, while dark sections use cream/yellow text. This is a design-level contrast adjustment, not a full automated WCAG audit or certification.

## Browser checks and known limitations

The package was checked with static source validation and link/asset inspection in the build environment. A full interactive browser session and manual screen-reader audit were not available in this sandbox, so console-error, pixel-level viewport, and assistive-technology behavior remain unverified. No screenshots are included because none were captured. Real HTTP 404 status behavior belongs to the hosting/application layer.
