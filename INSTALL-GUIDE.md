# Install guide: local WordPress + Elementor + mock blog + bk-tm2 layout

A local WordPress (Docker) with Elementor (free), a lightweight theme, a few add-on plugins, placeholder content that mirrors
the structure of an existing government-site blog (14 categories, 37 posts, poster pages, a menu) and a rebuilt home page layout
("bk-tm2": header, banner, services, commanders, news grid, videos, footer) made only with free Elementor widgets.
All text is placeholder. Names, phone numbers and addresses are fake.

## Quick start (one command)

Needs Docker Desktop (running) and the files in this repo. From the repo folder:

```powershell
# Windows (PowerShell)
.\setup.ps1
# optional: use a logo, by pointing at a folder that contains logo_imm2_509x602.png
.\setup.ps1 -AssetsPath "C:\path\to\assets-to-reuse"
```

```bash
# macOS / Linux / Git Bash
./setup.sh                      # optional: ./setup.sh /path/to/folder-with-logo
```

It takes about 2 minutes on a fresh machine, and about 40 seconds if everything is already there (it skips what exists).
It creates `.env` with random passwords, starts the containers, installs and activates the plugins and theme, sets pretty URLs,
loads the mock data and builds the layout. When it finishes it prints the three URLs:

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
| **Ultimate Addons for Elementor Lite** | free header/footer builder and the menu/logo/copyright widgets (Elementor's Theme Builder is Pro; its menu entry only shows an upgrade page) |
| **Essential Addons Lite** | the post grid used for the news section |
| **Contact Form 7** | forms (Elementor's Form widget is Pro) |

The first admin visit may open setup screens for UAE and Essential Addons; skip them.

## 3. The mock data and the layout

Two scripts in `mock-data/`, both safe to re-run:

| Script | What it does |
|---|---|
| `seed.php` | 17 categories, 37 placeholder posts (text + images, text only, poster-only, text + PDF), 9 pages (Home, News, 7 poster pages), a "Main menu", placeholder images, and the logo if mounted at `/assets`. Moves any previously scraped posts to the Trash. |
| `build-bk-tm2.php` | the Elementor layout: global colours and fonts (navy `#0A1E3C`, gold `#B8934A`, Sarabun / Noto Serif Thai), the header and footer templates (UAE Lite), the Home page, and a little Additional CSS |

```powershell
docker compose run --rm -u 33 -v "${PWD}/mock-data:/mock:ro" --entrypoint wp wpcli eval-file /mock/seed.php --path=/var/www/html
docker compose run --rm -u 33 -v "${PWD}/mock-data:/mock:ro" --entrypoint wp wpcli eval-file /mock/build-bk-tm2.php --path=/var/www/html
```

(Add `-v "<folder>:/assets:ro"` to use a logo.) Everything is tagged `_mock_data=1`:

```bash
$W post list --post_type=any --meta_key=_mock_data --fields=ID,post_type,post_title --path=/var/www/html
# remove (PERMANENT - review the list first)
$W post delete $($W post list --post_type=any --meta_key=_mock_data --format=ids --path=/var/www/html) --force --path=/var/www/html
```

**What the layout is made of** (all free): Elementor containers, Heading, Text Editor, Button, Icon, Image, HTML and Icon List widgets;
UAE Lite's Site Logo, Navigation Menu and Copyright widgets; Essential Addons Lite's Post Grid.
**Not reproduced:** the original's dark-mode toggle, language switch, hover animations and the "chips" filter above the news
(they are static buttons here). The menu colours and the news-card look are set in **Appearance → Customize → Additional CSS**
(the free menu widget has no colour controls); those rules are in `build-bk-tm2.php`.

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
templates and the Elementor data of every page, plus the Additional CSS.

What did **not** transfer (it lives in site settings, not content), found by test:

| Missing after import | Effect | Fix |
|---|---|---|
| Front page and posts page | the site showed the blog list, not Home | `mock-data/finish-import.php` |
| Menu location assignment | no header menu | same script |
| Site logo (theme setting) | no logo | same script |
| Active Elementor kit (global colours and fonts) | default Elementor colours and fonts | same script |
| Image and link URLs | pointed at the old address | `wp search-replace <old> <new> --all-tables` |

With those two steps the target rendered the same design (header, banner, services, commanders, news grid, footer).

```bash
$W plugin install wordpress-importer --activate --path=/var/www/html
$W import site.xml --authors=skip --user=admin --path=/var/www/html
docker compose run --rm -u 33 -v "${PWD}/mock-data:/mock:ro" --entrypoint wp wpcli eval-file /mock/finish-import.php --path=/var/www/html
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
| `setup.sh` in Git Bash: `'C:/Program Files/Git/mock/seed.php' does not exist` | already handled in the script (`MSYS_NO_PATHCONV`); if you run the docker commands by hand in Git Bash, prefix them with `MSYS_NO_PATHCONV=1`. |
| Importer: "Failed to import Media" | the source URL isn't reachable from the target (private address, firewall, or the old site is offline). |
