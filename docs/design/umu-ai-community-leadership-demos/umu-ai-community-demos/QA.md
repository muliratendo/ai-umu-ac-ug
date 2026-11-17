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

## Leadership extension checks

- Confirmed the leadership directory and all 12 required profile pages exist and are non-empty.
- Confirmed the directory includes every supplied executive role, patron, project coordination entry, campus, and listed faculty seat. Unnamed entries remain visible with honest labels or inline disclosures.
- Confirmed every directory entry either links to a valid profile or has an accessible inline detail disclosure.
- Confirmed every profile contains `#overview`, `#reporting-list`, `#biography`, and `#letters-to-the-community`, plus an expandable editorial-reference block and honest empty-state language for missing data.
- Confirmed only the supplied Project Manager → Executive Committee reporting edge is drawn as a reporting relationship; other relationships are labelled unconfirmed or coordination/oversight.
- Confirmed the directory has a semantic list alternative, labelled search/filter controls, relationship legend, and a clearly identified scrollable chart region.
- Confirmed `CONTENT_GUIDE.md` and `SOURCE_NOTES.md` are included and distinguish supplied content, placeholders, and unresolved governance questions.
- Confirmed three leadership-specific scripts are external, deferred, non-empty, and have distinct responsibilities.

The source Google Doc was not readable beyond its editor shell in this environment. MIT pages were readable through public page extraction. Full browser interaction, screen-reader, 200% zoom, and pixel-level viewport checks remain unverified in this sandbox.

## Revised proposal leadership checks

- Replaced the previous club-only leadership pages with the revised proposal-driven model.
- Confirmed persistent draft/consultation/approval disclosure and distinct status labels.
- Confirmed governance, three operational divisions, representation, technical categories, supporting arrangements, and institutional relationships are represented.
- Confirmed university offices are presented as institutional relationships, not operational subordinates.
- Confirmed the revised President name, named faculty representatives, Mother Kevin–Nsambya label, and updated Speaker/editorial responsibility boundary.
- Confirmed the required representative profile pages and four anchored sections on every profile.
- Confirmed `RELATIONSHIP_REGISTER.csv` includes typed edges, direction, source passage, approval status, uncertainty, and verification date.
- Confirmed old club-only profile pages were removed and unrelated existing demos were preserved.
- Confirmed all profile pages contain source-grounded content, empty states, expandable editorial guides, role-specific boundaries, and illustrative letter labels.

The revised Google Doc was not readable beyond its editor shell in this environment; the supplied proposal summary was used as the controlling source. MIT functional references were readable. Full browser interaction, screen-reader, 200% zoom, and pixel-level viewport checks remain unverified.
