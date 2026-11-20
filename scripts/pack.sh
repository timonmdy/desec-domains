#!/usr/bin/env bash
set -euo pipefail

root="$(cd "$(dirname "$0")/.." && pwd)"
out="${root}/dist/desec.blueprint"
zip_out="${root}/dist/desec.zip"

mkdir -p "${root}/dist"
rm -f "${out}" "${zip_out}"

cd "${root}"
if command -v zip >/dev/null 2>&1; then
  zip -r "${out}" conf.yml admin console dashboard migrations routers app \
    -x "*.DS_Store" >/dev/null
elif command -v powershell.exe >/dev/null 2>&1; then
  DESEC_ROOT="$(pwd -W)" powershell.exe -NoProfile -NonInteractive -Command \
    '$ErrorActionPreference = "Stop"; $root = $env:DESEC_ROOT; $paths = @("conf.yml", "admin", "console", "dashboard", "migrations", "routers", "app") | ForEach-Object { Join-Path $root $_ }; Compress-Archive -Path $paths -DestinationPath (Join-Path $root "dist/desec.zip") -Force; Move-Item (Join-Path $root "dist/desec.zip") (Join-Path $root "dist/desec.blueprint")'
else
  echo "Error: zip or Windows PowerShell (powershell.exe) is required to package the extension." >&2
  exit 1
fi

echo "Built ${out}"
echo "Install: copy to /var/www/pterodactyl and run: blueprint -install desec"
