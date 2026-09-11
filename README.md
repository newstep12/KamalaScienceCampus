# Kamala Science Campus — website

Static website for **Kamala Science Campus** (कमला साइन्स क्याम्पस), a
community-based campus in Dhungrebas, Kamalamai, Sindhuli, Bagmati Province,
Nepal — established 2066 BS (2009 AD), affiliated to Tribhuvan University and
the National Examinations Board, recognised by the UGC Nepal.

No build tools, no dependencies. Plain HTML/CSS/JS that any static host serves.

## Pages

| File | Page |
| --- | --- |
| `index.html` | Home — highlights, about summary, programs, facilities |
| `about.html` | History, mission, affiliation & recognition, facilities |
| `programs.html` | +2 Science (NEB) and B.Sc. General (TU) in detail |
| `admissions.html` | Eligibility, application steps, documents, FAQ |
| `scholarships.html` | Scholarship categories and how to claim |
| `contact.html` | Address, phone, map, enquiry form |

## Editing

Pages are assembled from shared parts so the header, footer and `<head>` stay
identical across all six pages:

```
src/partials/head.html     <head> contents (fonts, stylesheet, favicon)
src/partials/header.html   top bar + logo + navigation
src/partials/footer.html   footer + script tag
src/pages/*.html           the body content of each page
```

Edit files under `src/`, then regenerate the root `.html` files:

```bash
python3 build.py
```

Do **not** hand-edit the `.html` files in the project root — `build.py`
overwrites them. Content-only edits go in `src/pages/`; anything shared goes in
`src/partials/`.

Styling lives in `assets/css/styles.css` (colours are CSS variables at the top).
Behaviour — mobile menu, active nav link, the enquiry form — is in
`assets/js/main.js`.

## Before going live: fill in the placeholders

Search the project for `TODO` and `[ add`. These are the details I could not
verify and deliberately left blank rather than invent:

- **Campus email address** — in `src/partials/footer.html`, and in
  `src/pages/contact.html` (both the details table and the form's `data-mailto`
  attribute, which is what makes the enquiry form work).
- **Office hours** — `src/pages/contact.html`.
- **Fees** — intentionally not stated anywhere; the pages point people to the
  campus phone number instead, since community-campus fees change each session.
- **Photographs** — there are no images yet. Drop real campus photos into
  `assets/img/` and add them to the hero and About page; stock photos of a
  different campus would misrepresent the institution.
- **Map pin** — `contact.html` embeds a Google Maps search for "Dhungrebas,
  Kamalamai, Sindhuli". Replace the `src` with a precise embed link for the
  actual campus location when you have one.

Verified and already in place: phone `+977-47-520203`, established 2066 BS /
2009 AD, TU + NEB affiliation, UGC recognition, B.Sc. General = 4 years /
50 seats / English medium, and the scholarship categories.

## Local preview

```bash
python3 -m http.server 4321
```

Then open <http://localhost:4321>.

## Publishing on GitHub Pages

```bash
git add -A
git commit -m "Kamala Science Campus website"
git branch -M main
git remote add origin https://github.com/<your-user>/kamala-science-campus.git
git push -u origin main
```

Then in the repository: **Settings → Pages → Source: Deploy from a branch →
`main` / `(root)`**. The site appears at
`https://<your-user>.github.io/kamala-science-campus/`.

Because every link is relative and the site is served from the repository root,
it works both at that path and on a custom domain later.
