# UMU AI Community frontend demos

Open `index.html` or `demos.html` directly in a browser. The package also works from a local static server, for example:

```bash
python3 -m http.server 8000
```

Then open `http://localhost:8000/` from inside this directory. This is optional; no build tools or dependencies are required.

## Structure

There are 12 complete HTML pages, exactly one shared stylesheet at `assets/css/styles.css`, and three external JavaScript files separated by responsibility: `navigation.js` for the enhanced mobile menu, `demo-auth.js` for the explicitly simulated sign-in/sign-out interaction, and `accessibility.js` for focus enhancements. There are no image assets because the field-note/orbit art is CSS decoration.

## Page inventory and application mapping

- `index.html`: Home / public home page.
- `community.html`: Community / public community page with the four connected units and exploratory directions.
- `account.html`: Account / basic signed-in account view using fictional `Demo Member` and `member@example.test` data.
- `404.html`: Branded page-not-found view. It explicitly notes that the file itself does not emit an HTTP 404 status.
- `sign-in.html`: Authentication entry demonstration, corresponding to invite-only Google sign-in without connecting to Google.
- `auth-cancelled.html`: Cancelled or unfinished sign-in state.
- `auth-expired.html`: Invalid, expired, or already-used request state.
- `auth-denied.html`: Generic eligibility-denied state without revealing account eligibility details.
- `auth-unavailable.html`: Temporary provider/network failure state.
- `auth-disabled.html`: Sign-in disabled state with no retry CTA.
- `signed-out.html`: Local demo sign-out confirmation.
- `demos.html`: Review catalogue linking to every page and describing simulation limits.

## Simulation limitations

This is a static design demonstration. It performs no OAuth, API, analytics, cookie, backend, authorization, membership, password, or personal-data operation. The approved sign-in path is a normal relative link to `account.html`; local sign-out is a button enhancement with a direct no-JavaScript fallback link. The pages do not claim production authentication, production HTTP status handling, backend security, or accessibility certification.

## Design-source provenance

Visual direction was translated from the supplied `index.html` and `styles.css` reference: warm paper and cream surfaces, deep ink sections, teal emphasis, restrained orange/yellow accents, editorial headings, numbered cards, generous spacing, and the field-note/orbit motif. Product scope and authentication language were checked against commit `1c378dcdf585509e996b1db612334098a7c1d594` of `https://github.com/muliratendo/ai-umu-ac-ug`, specifically the referenced Home, Community, Account, NotFound, Layout, RecoveryNotice, `app.css`, and `web.php` files.

## Leadership extension

The package now includes `leadership.html` plus 12 profile pages covering the required named roles, representative/group variants, and editorial placeholders. `CONTENT_GUIDE.md` describes the content model and `SOURCE_NOTES.md` records the references, source gaps, and design adaptations. Leadership navigation is available from the original pages.

## Revised proposal leadership model

The leadership section now follows the 17 November 2026 UMU-AI Community Proposal (Draft), replacing the previous club-only structure. It includes governance, three operational divisions, campus/faculty representation, institutional relationships, required representative profiles, a relationship register, and editorial handoff guidance.
