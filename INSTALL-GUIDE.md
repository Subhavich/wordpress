# Install guide: the foundry (WordPress + Elementor) for the two Immigration Division 2 sites

> **Start with [FOUNDRY.md](FOUNDRY.md)**: it explains how this repo is organised as a shared *foundry* that two developers fork, one per site.
> This guide covers installing and running it.

A local WordPress (Docker) with Elementor (free), a lightweight theme, a few add-on plugins, the shared **foundry** (design system, component
library and a guide page inside Elementor) and the layout of **site one** (starter) or **site two** (placeholder blog + the bk-tm2 home page),
all made only with free Elementor widgets. All text is placeholder. Names, phone numbers and addresses are fake.

## Quick start (one command)

Needs Docker Desktop (running) and the files in this repo. From the repo folder:

```powershell
# Windows (PowerShell): builds BOTH sites as pages /site-one/ and /site-two/ (default)
.\setup.ps1
.\setup.ps1 -Site one           # only site one  (or: -Site two)
# optional: use a logo, by pointing at a folder that contains logo_imm2_509x602.png
.\setup.ps1 -AssetsPath "C:\path\to\assets-to-reuse"
```

```bash
# macOS / Linux / Git Bash
./setup.sh                      # both sites
./setup.sh one                  # only site one;  optional logo folder: ./setup.sh both /path/to/folder-with-logo
```

Each site is one WordPress **page** with a custom slug (default `/site-one/` and `/site-two/`): see [FOUNDRY.md](FOUNDRY.md). Each developer can run their own copy of the stack and build just their site.
It takes about 2 minutes on a fresh machine, and about 40 seconds if everything is already there (it skips what exists).
It creates `.env` with random passwords, starts the containers, installs and activates the plugins and theme, sets pretty URLs,
loads the site's content, builds the foundry (Elementor globals, component library, guide page) and then the site's layout.
When it finishes it prints the URLs below, plus the Component guide URL (a private page; log in first):

- Site: http://localhost:8080
- Admin: http://localhost:8080/wp-admin (user `admin`; the password is `WP_ADMIN_PASSWORD` in `.env`)
- Elementor home: http://localhost:8080/wp-admin/admin.php?page=elementor-home

Options (`setup.ps1`): `-SkipMock` installs WordPress and the plugins only; `-Reset` first **destroys** the database and files
(`docker compose down -v`).

> Tested: `setup.ps1` from a completely clean state (new containers and volumes, 108 s) and re-run on an existing site (38 s);
> `setup.sh` re-run on an existing site under Git Bash on Windows. Not tested on Linux or macOS.

| Piece | Version tested |
|---|---|
| WordPress | latest (`wordpress:latest` image) |
| Elementor (free) | 4.3.5 |
| Hello Elementor theme | 3.5.1 |
| Contact Form 7 | 6.2.2 |
| Ultimate Addons for Elementor Lite (header/footer builder, slug `header-footer-elementor`) | 2.9.5 |
| Essential Addons for Elementor Lite | 6.8.6 |

## 1. Prerequisites

- Docker Desktop (running; wait until it says "Engine running")
- Ports 8080 and 8081 free (change `WP_PORT` / `PMA_PORT` in `.env` if not)
- Git (only to clone this repo)

## 2. Step by step (what the setup script does)

```bash
git clone https://github.com/youneverwalkalone/wordpress.git
cd wordpress
cp .env.example .env            # then replace every change_me_... value; .env is git-ignored
docker compose up -d
docker compose logs wpcli       # wait for: "WordPress installed successfully."
```

> **Windows line endings:** if `wpcli` exits with `set: illegal option -`, `wp-install.sh` has CRLF endings. The repo ships a
> `.gitattributes` (`*.sh text eol=lf`). Convert the file to LF (the setup script does this for you) and run
> `docker compose up -d wpcli`.

### WP-CLI shortcut

The `wpcli` service has the installer as its entrypoint, so run other commands like this (`-u 33` is the web server user, so
files get the right owner):

```bash
docker compose run --rm -u 33 --entrypoint wp wpcli <command> --path=/var/www/html
```

```powershell
function wp { docker compose run --rm -u 33 --entrypoint wp wpcli @args --path=/var/www/html }
wp plugin list
```

### Plugins and theme

```bash
W="docker compose run --rm -u 33 --entrypoint wp wpcli"
$W plugin install elementor --activate --path=/var/www/html
$W theme  install hello-elementor --activate --path=/var/www/html
$W plugin install contact-form-7 --activate --path=/var/www/html
$W plugin install header-footer-elementor --activate --path=/var/www/html
$W plugin install essential-addons-for-elementor-lite --activate --path=/var/www/html
$W rewrite structure '/%postname%/' --hard --path=/var/www/html
```

| Plugin / theme | Why |
|---|---|
| **Elementor** (free) | page builder |
| **Hello Elementor** | minimal theme made for Elementor |
| **Ultimate Addons for Elementor Lite** | free header/footer builder, plus the Site Logo and Copyright widgets (Elementor's Theme Builder is Pro; its menu entry only shows an upgrade page) |
| **Essential Addons Lite** | the Post Grid (news section) and the Simple Menu (header menu; unlike UAE Lite's menu widget it has colour controls, so no CSS is needed) |
| **Contact Form 7** | forms (Elementor's Form widget is Pro) |

The first admin visit may open setup screens for UAE and Essential Addons; skip them.

## 3. The scripts (foundry, then a site)

Three scripts run in this order (`setup.ps1` / `setup.sh` do it for you). All are safe to re-run.

| Script | What it does |
|---|---|
| `sites/<site>/seed.php` | the site's content. Site two: 17 categories (real names and ids), **78 realistic placeholder posts** shaped like the real blog (real per-category counts, date windows and length mix, invented Thai text; see FOUNDRY.md), 9 pages, a "Main menu". Site one: 5 pages and "Site one menu" (no posts, like the real site). Both: placeholder images and the logo if mounted at `/assets`. |
| `foundry/build-foundry.php` | the shared base: Elementor Global Colors and Fonts (navy `#0A1E3C`, gold `#B8934A`, Sarabun / Noto Serif Thai, with tablet and mobile sizes), the component library (7 live Custom Blocks + 7 Saved Templates) and the private **Component guide** page. |
| `sites/<site>/build.php` | the site's page (`/site-one/`, `/site-two/`: header + sections + footer on the Canvas template), assembled from the foundry components plus the sections only that site has. Site two also keeps its global Home page, header and footer. |

```powershell
$m = @('-v', "${PWD}/foundry:/foundry:ro", '-v', "${PWD}/sites:/sites:ro")
docker compose run --rm -u 33 @m --entrypoint wp wpcli eval-file /sites/two/seed.php --path=/var/www/html
docker compose run --rm -u 33 @m --entrypoint wp wpcli eval-file /foundry/build-foundry.php --path=/var/www/html
docker compose run --rm -u 33 @m --entrypoint wp wpcli eval-file /sites/two/build.php --path=/var/www/html
```

(Add `-v "<folder>:/assets:ro"` to use a logo; replace `two` with `one` for site one.) Everything is tagged `_mock_data=1`:

```bash
$W post list --post_type=any --meta_key=_mock_data --fields=ID,post_type,post_title --path=/var/www/html
# remove (PERMANENT - review the list first)
$W post delete $($W post list --post_type=any --meta_key=_mock_data --format=ids --path=/var/www/html) --force --path=/var/www/html
```

### How the layout is built (so you can edit it)

- **Two layers per section.** Each section is an *outer* container (full width; holds the background colour and the vertical
  padding) with one *inner* container (Content Width "Boxed", **1280 px**; holds the content). The inner container's boxed width is
  what caps the content at 1280 and centres it; on narrower screens it shrinks. Every section, the header and the footer use the same
  pair, so everything lines up on the same left and right edge.
- **Layer names.** All 201 containers and widgets are named in the Navigator ("Online services > outer (full width)",
  "Online services > inner (max 1280)", "Service card: TDAC > button" ...).
- **Responsive.** Card rows are Elementor **Grid** containers with their own column counts: shortcuts 6 / 3 / 2, service cards 3 / 2 / 1,
  commanders 3 / 3 / 1, videos 4 / 2 / 1, footer 4 / 2 / 1 (desktop / tablet / mobile). Section padding and gaps shrink on tablet and
  mobile; the menu becomes a hamburger at the tablet breakpoint. Checked at 1440, 768 and 375 px with no horizontal scrolling.
- **Globals.** Colours and fonts reference Elementor Global Colors and Global Fonts (Site Settings), so changing a global restyles the
  page. Font sizes for tablet and mobile live in the Global Fonts too. A few colours are literal on purpose: transparent
  backgrounds, translucent borders and shadows.
- **No custom CSS, no HTML widgets, no inline styles.** The gold accent bar beside each section title is a container border, the
  rule is a Divider widget, and the "view all" links are Button widgets.
- **Widgets:** Elementor containers, Heading, Text Editor, Button, Icon, Image, Divider, Icon List and Social Icons; UAE Lite's
  Site Logo and Copyright; Essential Addons Lite's Simple Menu and Post Grid.
- **Not reproduced from the original design:** the dark-mode toggle, language switch, hover animations, and a working filter on the news
  (the chips above it are static buttons).
- **Your hand edits are protected.** See the next section.

### Will my edits in the Elementor editor persist?

Yes. Elementor saves to the WordPress database (a Docker volume), so edits survive closing the browser, restarting Docker,
rebooting and `docker compose down`. They are **lost only if** you wipe the volumes (`docker compose down -v`, or `setup.ps1 -Reset`).

Re-running the scripts is safe too. After every build the script remembers a fingerprint of what it wrote. On the next run, if the
Home page, the Header, the Footer or Elementor's Global Colours/Fonts no longer match it (because you edited them), that item is
**kept** and the script prints `KEPT YOUR EDITS ...`. Items you did not touch are still rebuilt. `setup.ps1`, `setup.sh` and
the site `seed.php` files are therefore safe to re-run. Tested: simulated edits to the page and a global colour survived both the build script and
a full `setup.ps1`.

To deliberately throw your edits away and rebuild the original design, set `BK_FORCE=1`:

```powershell
docker compose run --rm -u 33 -e BK_FORCE=1 -v "${PWD}/foundry:/foundry:ro" -v "${PWD}/sites:/sites:ro" --entrypoint wp wpcli eval-file /sites/two/build.php --path=/var/www/html
```

Caveats: edits are protected per item, not per widget (if you changed one widget on the Home page, the whole page is kept as it is,
so a newer version of the script will not update the untouched parts of that page); and the guard only covers what this script builds
(pages you create yourself are never touched). **Back up before big changes:** Elementor → Tools → Export Kit, or a database dump
(`docker compose exec db mariadb-dump ...`).

## 4. First steps in Elementor

1. **Elementor → Home**: http://localhost:8080/wp-admin/admin.php?page=elementor-home
2. **Pages → Home → Edit with Elementor** (or http://localhost:8080/wp-admin/post.php?post=176&action=elementor; the ID may differ on your install).
   The Navigator panel lists the 8 sections.
3. Header and footer: **UAE → Header & Footer**.
4. Replace the banner placeholder with an Image or Image Carousel widget; swap the placeholder names and contact details.
5. Forms: **Contact → Contact Forms** (Contact Form 7), then paste the shortcode into a page.

## 5. Can I put this on any WordPress site? (tested answer)

**Not "any", and not with one click.** I tested moving it from this site to a separate, blank WordPress (same plugins installed):
export from this site (`wp export`, or Tools → Export), import with the WordPress Importer, then fix settings.

What transferred **by itself** (in the XML export): posts, pages, categories, media files, the nav menu items, the header and footer
templates and the Elementor data of every page.

What did **not** transfer (it lives in site settings, not content), found by test:

| Missing after import | Effect | Fix |
|---|---|---|
| Front page and posts page | the site showed the blog list, not Home | `foundry/finish-import.php` |
| Menu location assignment | no header menu | same script |
| Site logo (theme setting) | no logo | same script |
| Active Elementor kit (global colours and fonts) | default Elementor colours and fonts | same script |
| Image and link URLs | pointed at the old address | `wp search-replace <old> <new> --all-tables` |

With those two steps the target rendered the same design (header, banner, services, commanders, news grid, footer).

```bash
$W plugin install wordpress-importer --activate --path=/var/www/html
$W import site.xml --authors=skip --user=admin --path=/var/www/html
docker compose run --rm -u 33 -v "${PWD}/foundry:/foundry:ro" --entrypoint wp wpcli eval-file /foundry/finish-import.php --path=/var/www/html
$W search-replace 'http://old-site' 'https://new-site' --all-tables --path=/var/www/html
$W elementor flush-css --path=/var/www/html
```

**Conditions for it to work**

- It must be self-hosted WordPress where you can install plugins (not a locked-down hosted plan).
- The same plugins must be installed and active on the target: Elementor, UAE Lite, Essential Addons Lite, Contact Form 7, and ideally the Hello Elementor theme.
- The source site must be reachable by URL so the importer can download the media (or copy `wp-content/uploads` yourself).
- Run `finish-import.php` only on a new or staging site: it changes the front page, menu location, logo and Elementor globals.
- A different theme can change spacing and fonts slightly; UAE Lite's header/footer replace most themes' own.
- Newer or older Elementor versions than 4.3.5 may need a "Regenerate CSS" and a visual check.

For an exact copy of a whole site (including settings), use a migration plugin (Duplicator, All-in-One WP Migration) instead.

## 6. Stop, reset, troubleshoot

```bash
docker compose down        # stop, keep data
docker compose down -v     # stop AND WIPE the database and all WordPress files
```

| Symptom | Fix |
|---|---|
| `failed to connect to the docker API` | Docker Desktop isn't running; start it. (`setup.ps1` starts it for you.) |
| `wpcli` exits with `set: illegal option` | CRLF line endings; see the note in section 2. |
| `Unable to create directory wp-content/uploads...` from wp-cli | run wp-cli with `-u 33`. |
| Layout looks unstyled in Elementor | Elementor → Tools → Regenerate CSS & Data (or `wp elementor flush-css`). |
| Port already in use | change `WP_PORT` / `PMA_PORT` in `.env`. |
| `/news/` etc. show the home page | pretty permalinks missing: `wp rewrite structure '/%postname%/' --hard`. |
| `setup.sh` in Git Bash: `'C:/Program Files/Git/foundry/...' does not exist` | already handled in the script (`MSYS_NO_PATHCONV`); if you run the docker commands by hand in Git Bash, prefix them with `MSYS_NO_PATHCONV=1`. |
| Importer: "Failed to import Media" | the source URL isn't reachable from the target (private address, firewall, or the old site is offline). |
