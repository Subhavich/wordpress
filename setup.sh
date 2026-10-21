#!/usr/bin/env bash
# One-command setup of the FOUNDRY for one site (macOS / Linux / Git Bash): WordPress + Elementor (free) + add-ons + the shared foundry
# (Elementor globals, component library, guide page) + that site's content and layout.
# Safe to re-run (layouts you edited by hand are kept). Needs Docker. Use ONE WordPress install per site.
# Usage:  ./setup.sh [one|two|both] [path-to-folder-with-logo_imm2_509x602.png]     (default: both = pages /site-one/ and /site-two/)
set -u
export MSYS_NO_PATHCONV=1 MSYS2_ARG_CONV_EXCL='*'   # Git Bash on Windows would otherwise rewrite /foundry/... paths
cd "$(dirname "$0")"
SITE=both; ASSETS=""
for a in "$@"; do case "$a" in one|two|both) SITE="$a" ;; *) ASSETS="$a" ;; esac; done
if [ "$SITE" = both ]; then SITES=(one two); else SITES=("$SITE"); fi
say() { printf '==> %s\n' "$*"; }

docker info >/dev/null 2>&1 || { echo "Docker is not running. Start Docker and run this again."; exit 1; }

if [ ! -f .env ]; then
  say "Creating .env with random passwords"
  rnd() { LC_ALL=C tr -dc 'A-Za-z0-9' </dev/urandom | head -c 20; }
  sed -e "s/change_me_password/$(rnd)/" -e "s/change_me_root_password/$(rnd)/" -e "s/change_me_admin_password/$(rnd)/" .env.example > .env
fi
sed -i.bak 's/\r$//' wp-install.sh && rm -f wp-install.sh.bak   # must be LF

say "Starting containers"
docker compose up -d >/dev/null 2>&1
for i in $(seq 1 60); do
  docker compose logs wpcli 2>&1 | grep -qE 'WordPress installed successfully|already installed' && break
  sleep 5
done

wp() { docker compose run --rm -u 33 --entrypoint wp wpcli "$@" --path=/var/www/html 2>&1 | grep -vE '^\s*(Container|time=)|No services to build|Failed to create directory'; return "${PIPESTATUS[0]}"; }

say "Installing plugins and theme (skips what is already installed)"
for p in elementor contact-form-7 header-footer-elementor essential-addons-for-elementor-lite; do
  wp plugin is-installed "$p" >/dev/null || wp plugin install "$p" >/dev/null
  wp plugin activate "$p" >/dev/null
done
wp theme is-installed hello-elementor >/dev/null || wp theme install hello-elementor >/dev/null
wp theme activate hello-elementor >/dev/null
wp rewrite structure '/%postname%/' --hard >/dev/null

MOUNTS=(-v "$PWD/foundry:/foundry:ro" -v "$PWD/sites:/sites:ro")
[ -n "$ASSETS" ] && [ -d "$ASSETS" ] && MOUNTS+=(-v "$(cd "$ASSETS" && pwd):/assets:ro")
STEPS=(); for s in "${SITES[@]}"; do STEPS+=("sites/$s/seed.php"); done; STEPS+=(foundry/build-foundry.php); for s in "${SITES[@]}"; do STEPS+=("sites/$s/build.php"); done
for f in "${STEPS[@]}"; do
  say "Running $f"
  docker compose run --rm -u 33 "${MOUNTS[@]}" --entrypoint wp wpcli eval-file "/$f" --path=/var/www/html 2>&1 | grep -E '^(DONE|built|foundry|sections|standalone|menu|logo|no logo|GD:|home page|KEPT|guide:)|rror'
done

PORT=$(grep -E '^WP_PORT=' .env | cut -d= -f2); PORT=${PORT:-8080}
echo
echo "Site:      http://localhost:$PORT"
echo "Admin:     http://localhost:$PORT/wp-admin   (user/password: WP_ADMIN_USER / WP_ADMIN_PASSWORD in .env)"
echo "Elementor: http://localhost:$PORT/wp-admin/admin.php?page=elementor-home"
[ "$SITE" != two ] && echo "Site one:  http://localhost:$PORT/site-one/"
[ "$SITE" != one ] && echo "Site two:  http://localhost:$PORT/site-two/"
echo "Guide:     http://localhost:$PORT/component-guide/   (private page: log in first)"
