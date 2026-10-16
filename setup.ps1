<#
.SYNOPSIS
  One-command setup: WordPress + Elementor (free) + add-on plugins + mock blog data + the bk-tm2 layout.

.DESCRIPTION
  Safe to re-run: every step checks what already exists.
  Needs Docker Desktop. Run from the repo folder:   .\setup.ps1
  Options:
    -AssetsPath <folder>   folder containing logo_imm2_509x602.png (optional, used as the site logo)
    -SkipMock              install WordPress + plugins only (no mock content, no layout)
    -Reset                 DESTROYS the database and WordPress files first (docker compose down -v)
#>
param(
    [string]$AssetsPath = '',
    [switch]$SkipMock,
    [switch]$Reset
)
Set-Location $PSScriptRoot
function Say($m) { Write-Host "==> $m" -ForegroundColor Cyan }

# 1. Docker
docker info *> $null
if ($LASTEXITCODE -ne 0) {
    $dd = 'C:\Program Files\Docker\Docker\Docker Desktop.exe'
    if (Test-Path $dd) { Say 'Starting Docker Desktop...'; Start-Process $dd }
    for ($i = 0; $i -lt 60; $i++) { Start-Sleep 5; docker info *> $null; if ($LASTEXITCODE -eq 0) { break } }
    if ($LASTEXITCODE -ne 0) { throw 'Docker is not running. Start Docker Desktop and run this script again.' }
}

# 2. .env with random local passwords (only if missing)
if (-not (Test-Path .env)) {
    Say 'Creating .env with random passwords'
    $rnd = { -join ((48..57) + (65..90) + (97..122) | Get-Random -Count 20 | ForEach-Object { [char]$_ }) }
    $c = Get-Content .env.example -Raw
    $c = $c -replace 'change_me_password', (& $rnd) -replace 'change_me_root_password', (& $rnd) -replace 'change_me_admin_password', (& $rnd)
    Set-Content -Path .env -Value $c -Encoding ascii -NoNewline
}

# 3. wp-install.sh must have LF line endings (Git on Windows may have converted it)
$sh = [IO.File]::ReadAllText("$PSScriptRoot\wp-install.sh")
if ($sh.Contains("`r`n")) { [IO.File]::WriteAllText("$PSScriptRoot\wp-install.sh", $sh.Replace("`r`n", "`n"), (New-Object Text.UTF8Encoding $false)) }

if ($Reset) { Say 'Removing old containers and volumes'; docker compose down -v 2>&1 | Out-Null }

# 4. Start the stack and wait for the installer
Say 'Starting containers'
docker compose up -d 2>&1 | Out-Null
for ($i = 0; $i -lt 60; $i++) {
    $log = docker compose logs wpcli 2>&1 | Out-String
    if ($log -match 'WordPress installed successfully|already installed') { break }
    Start-Sleep 5
}

function wp {
    docker compose run --rm -u 33 --entrypoint wp wpcli @args --path=/var/www/html 2>&1 |
        Where-Object { $_ -notmatch '^\s*(Container|time=)' -and $_ -notmatch 'No services to build|NativeCommandError|CategoryInfo|FullyQualifiedErrorId|^At line|^\+|^docker :|Failed to create directory|Warning: Some code is trying|^#\d+ ' }
}

# 5. Plugins and theme
Say 'Installing plugins and theme (skips what is already installed)'
foreach ($p in 'elementor', 'contact-form-7', 'header-footer-elementor', 'essential-addons-for-elementor-lite') {
    wp plugin is-installed $p; if ($LASTEXITCODE -ne 0) { wp plugin install $p | Out-Null }
    wp plugin activate $p | Out-Null
}
wp theme is-installed hello-elementor; if ($LASTEXITCODE -ne 0) { wp theme install hello-elementor | Out-Null }
wp theme activate hello-elementor | Out-Null
wp rewrite structure '/%postname%/' --hard | Out-Null

# 6. Mock data + layout
if (-not $SkipMock) {
    $mounts = @('-v', "$PSScriptRoot\mock-data:/mock:ro")
    if ($AssetsPath -and (Test-Path $AssetsPath)) { $mounts += @('-v', "$((Resolve-Path $AssetsPath).Path):/assets:ro") }
    foreach ($f in 'seed.php', 'build-bk-tm2.php') {
        Say "Running mock-data/$f"
        docker compose run --rm -u 33 @mounts --entrypoint wp wpcli eval-file "/mock/$f" --path=/var/www/html 2>&1 |
            Where-Object { $_ -match '^(DONE|built|elements|menu|logo|no logo|GD:|home page)|rror' }
    }
}

# 7. Done
$e = @{}; Get-Content .env | ForEach-Object { if ($_ -match '^([A-Z_]+)=(.*)$') { $e[$Matches[1]] = $Matches[2] } }
$port = if ($e.WP_PORT) { $e.WP_PORT } else { '8080' }
Write-Host ''
Write-Host "Site:      http://localhost:$port" -ForegroundColor Green
Write-Host "Admin:     http://localhost:$port/wp-admin   (user: $($e.WP_ADMIN_USER), password is WP_ADMIN_PASSWORD in .env)" -ForegroundColor Green
Write-Host "Elementor: http://localhost:$port/wp-admin/admin.php?page=elementor-home" -ForegroundColor Green
