# The foundry: one base, two sites, two developers

This repo is a **foundry** for the two Immigration Division 2 websites. It holds everything the sites share (design system, components,
tooling) so that each site is a thin layer on top. **Each developer forks it and works on one site.**

| | Site | Design | Folder | Page | Status |
|---|---|---|---|---|---|
| **one** | admin-division2.immigration.go.th (ฝ่ายอำนวยการ บก.ตม.2) | `admin-tm2` | `sites/one/` | `/site-one/` | **starter**: shared sections built, 6 sections left as named TODOs |
| **two** | division2.immigration.go.th (กองบังคับการตรวจคนเข้าเมือง 2) | `bk-tm2` | `sites/two/` | `/site-two/` | home page built, realistic placeholder blog (78 posts) |

Each site is **one WordPress page** with a custom slug (default `site-one` / `site-two`; change it in the page's permalink or with the
`SITE_SLUG` environment variable). A page carries its own header, sections and footer on Elementor's **Canvas** template, so both sites can live side by
side in one WordPress without fighting over the global header/footer. Each site has its own menu ("Site one menu"; site two keeps "Main menu").

## Layout of the repo

```
foundry/                 SHARED. Owned by the foundry maintainer. Changed by pull request.
  lib.php                design tokens + builders + the shared components (the "contract")
  build-foundry.php      Elementor globals, component library (live blocks + templates), the Component guide page
  finish-import.php      settings an XML export does not carry (front page, menu, logo, active kit)
  VERSION, CHANGELOG.md  bump on every change to lib.php / build-foundry.php
sites/
  one/  seed.php build.php    site one only (dev A)
  two/  seed.php build.php import-real.php extract-backup.py    site two only (dev B)
setup.ps1 / setup.sh     one-command install:  .\setup.ps1 [-Site one|two|both]   (default: both)
docker-compose.yml ...   the local WordPress stack
```

Rule of thumb: **if both sites need it, it belongs in `foundry/`. If only one site needs it, it stays in `sites/<site>/`.**

## What the foundry gives you

1. **Design system in Elementor.** Global Colors and Global Fonts (with tablet and mobile sizes) and a 1280 px content width. Everything references them: no hard-coded colours or font sizes.
2. **Shared components** (functions in `foundry/lib.php`, each returns Elementor data):
   `$cHeader`, `$cFooter`, `$cStatusBand`, `$cServices`, `$cLeadership`, `$cVideos`, `$cShell` (empty section skeleton), `$cTodo` (labelled placeholder).
3. **A component library inside Elementor**, built from the same data:
   - **Live blocks** (UAE "Custom Blocks"): embed with the Shortcode widget `[hfe_template id="…"]`. Edit the block once, every page that embeds it updates.
   - **Saved Templates** (Templates → Saved Templates, category *Shared components*): insert as an independent copy.
4. **A "Component guide" page** (private page, slug `component-guide`) with a page map of both sites, a component matrix, a live specimen of every shared component, how-to steps and the rules.
5. **Hand-edit protection.** Re-running any build never overwrites something you edited in Elementor (it prints `KEPT YOUR EDITS`); `BK_FORCE=1` forces a rebuild.

## Structure rules (every section, every site, every page)

- One **outer** full-width container (background colour, vertical padding) + one **inner** container with Content Width *Boxed* = **1280 px** (the content). Grids sit inside the inner container.
- Cards live in **Grid** containers with their own columns for desktop, tablet and mobile.
- Name every layer `Section > part`. Colours and fonts only from Global Colors / Global Fonts. No custom CSS, no HTML widgets.
- Use only **free** widgets (Elementor core, Ultimate Addons Lite, Essential Addons Lite). Forms use Contact Form 7.

## Placeholder data: how close to the real sites?

Site two's blog is **realistic placeholder** data, shaped by an audit of the real site. Nothing is copied from it: titles and text are invented Thai.

| | Real site | Placeholder |
|---|---|---|
| Posts | 89 articles (78 news-type + 11 static pages) | 78 posts + the static pages as WordPress pages |
| Categories | 14 in use, real names and ids | same names and ids, real per-category counts |
| Dates | each category has its own window (e.g. commander missions 2026 only) | every post inside its category's real window |
| Body length | median 696, p90 4,066 chars; avg 3,000+ for operations / ITA | per-category averages within about 10% (e.g. operations 3,018 vs 3,148) |
| Empty bodies | ~24% | ~10% (lower) |
| Images per image post | ~10 (up to 24 on ITA posts) | avg 2.3, max 9 (lower) |
| Views | median 81, max 6,280, shown on every card | similar curve (median 73, max 3,681), stored as a custom field `_mock_views`, **not displayed** (WordPress has no view counter) |
| Dates shown in | Buddhist era (2569) | Gregorian |
| Author | "Admin" | "Admin" |

### Real content (from the backup)

The placeholder blog can be replaced by the **real** one from the crawl backup (`backup.zip`, kept outside the repo, 2.9 GB):

```
# 1. backup -> import pack (89 articles, images, attachments; needs Python + beautifulsoup4)
python -X utf8 sites/two/extract-backup.py <backup>/division2.immigration.go.th <pack-folder>
# 2. pack -> WordPress (mount the pack as /pack; IMPORT_STATUS=draft on a live site)
docker compose run --rm -u 33 -v "${PWD}/sites:/sites:ro" -v "<pack-folder>:/pack:ro" --entrypoint wp wpcli eval-file /sites/two/import-real.php --path=/var/www/html
```

It creates the 89 posts in their real categories (real titles, dates, view counts, bodies), uploads about 530 images and files to the Media Library and rewrites the body links, sets each post's first image as the featured image, and trashes the placeholder posts. Re-running skips what is already imported. 11 of the 100 crawled article pages are dead-link shells and are skipped; 20 of the 89 articles have no text, image or file in the backup (same as the live site). Site one has no pack: the backup holds only 4 static pages for it.

Site one has **no blog posts**, like the real site: just 5 pages and a menu. Not covered yet: the real homepage carousel, documents (hundreds of PDFs), video list, units list, contact details and forms.

## Workflow for two developers

1. **Fork** this repo (each dev gets their own fork). Add the original as `upstream`:
   `git remote add upstream <foundry repo url>`
2. **Run it locally**: `.\setup.ps1` builds both pages in your own WordPress; `.\setup.ps1 -Site one` builds only site one.
3. **Work only in your folder**: `sites/one/` or `sites/two/`. Build new sections with the foundry building blocks (`$section`, `$grid`, `$stack`, `$heading`, `$text`, `$button`, …).
4. **Pull shared updates** regularly: `git fetch upstream && git merge upstream/main`. Because you only edit your own folder, conflicts should not happen.
5. **Need something in both sites?** Do not copy it between sites. Add a component to `foundry/lib.php` (and, if useful, to the library in `build-foundry.php`) in a pull request to the foundry. Keep component signatures stable: add options, don't rename; bump `foundry/VERSION` and note it in `CHANGELOG.md`.
6. **Finished a site section** that the other site could reuse (for example a contact card + form)? Move it into the foundry as a component, then both sites call it.

`.github/CODEOWNERS` encodes this (replace the placeholder usernames).

## Adding a component to the foundry (checklist)

1. Write it as a closure in `foundry/lib.php` (same style as `$cStatusBand`): takes content as arguments, returns a section, follows the structure rules, named layers.
2. List it in the header comment of `lib.php` (the contract).
3. Add it to `$components` in `foundry/build-foundry.php` so it appears as a live block + template, and add it to the page map / matrix in the guide.
4. Bump `foundry/VERSION`, add a line to `foundry/CHANGELOG.md`.
5. Test: `.\setup.ps1` on a clean stack, then compare the data fingerprints (`_bk_build_hash`) of the pages: a site build must keep producing identical data unless you meant to change it.

## What is not solved yet (be aware)

- **Live blocks only update inside one WordPress install.** Two sites on two WordPress installs each have their own copy of the blocks (rebuilt from the foundry by the same script). True cross-site sharing would need Elementor Pro or a multisite.
- Elementor free has no *global widgets*; the live-block route is UAE Lite's shortcode, which cannot be edited inline inside a page.
- The standalone pages carry their own header and footer, so a change to the header must be made in each page (or in the foundry's `$cHeader` and rebuilt). Pages you edited by hand are kept; rebuild with `BK_FORCE=1` to take the foundry change.
- Real content import (real Thai posts, images, documents) is a separate step; see the audit (`BLOG-AUDIT.md`, kept outside this repo).
