#!/usr/bin/env bash
# One-command setup (macOS / Linux / Git Bash): WordPress + Elementor (free) + add-ons + mock data + bk-tm2 layout.
# Safe to re-run. Needs Docker. Usage:  ./setup.sh [path-to-folder-with-logo_imm2_509x602.png]
set -u
export MSYS_NO_PATHCONV=1 MSYS2_ARG_CONV_EXCL='*'   # Git Bash on Windows would otherwise rewrite /mock/... paths
cd "$(dirname "$0")"
ASSETS="${1:-}"
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

MOUNTS=(-v "$PWD/mock-data:/mock:ro")
[ -n "$ASSETS" ] && [ -d "$ASSETS" ] && MOUNTS+=(-v "$(cd "$ASSETS" && pwd):/assets:ro")
for f in seed.php build-bk-tm2.php; do
  say "Running mock-data/$f"
  docker compose run --rm -u 33 "${MOUNTS[@]}" --entrypoint wp wpcli eval-file "/mock/$f" --path=/var/www/html 2>&1 | grep -E '^(DONE|built|elements|menu|logo|no logo|GD:|home page)|rror'
done

PORT=$(grep -E '^WP_PORT=' .env | cut -d= -f2); PORT=${PORT:-8080}
echo
echo "Site:      http://localhost:$PORT"
echo "Admin:     http://localhost:$PORT/wp-admin   (user/password: WP_ADMIN_USER / WP_ADMIN_PASSWORD in .env)"
echo "Elementor: http://localhost:$PORT/wp-admin/admin.php?page=elementor-home"
