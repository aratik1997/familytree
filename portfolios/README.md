# Khandani Legacy — the eight portfolios

React + Vite + Tailwind + daisyUI + Motion. One page per person, built eight
times into eight self-contained folders.

## Why it builds eight times

Each portfolio becomes its own subdomain. A single build with eight entry
points would put the JavaScript and CSS in one shared `assets/` directory at
the top of `dist/`, which none of the subdomains could reach — each one only
ever sees its own folder. So the person is chosen by an env var and each build
gets its own complete output.

## Why there is no Express

The site is hosted on Apache/PHP shared hosting, which has no Node runtime.
Anything with a server process could not be started there. Everything here
therefore compiles to static files, which that hosting serves perfectly well.

## Commands

    npm install
    npm run build     # writes dist/<person>/ for all eight
    npm run dev       # VITE_PERSON=maria npm run dev, to work on one
    node scripts/verify.mjs   # loads each built page in a real browser

`verify.mjs` opens every build in Chromium, checks React actually painted,
clicks both switches, confirms the document responded, and writes a screenshot
to `shots/`. A green build only proves the bundle was written; this is what
proves the page works.

## Photographs

Drop them in `public/img/` named after the person — `ansary.jpg`, `atik.jpg`
and so on — then rebuild. Until a file exists the page shows a placeholder
naming the file it wants.

## Editing the words

Everything either page says, in both languages, is in `src/data.js`. A
correction to a degree, a language score or a line of a speech is a one-line
edit there rather than a hunt through a component.
