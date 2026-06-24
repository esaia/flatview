#!/usr/bin/env bash
#
# Run the full dev stack reachable from other devices on your LAN (e.g. an
# iPhone on the same Wi-Fi). Auto-detects your Mac's LAN IP each run, binds
# Laravel and Vite to it, and prints the URL to open on your phone.
#
# Usage: composer dev:mobile   (or: bash scripts/dev-mobile.sh)

set -euo pipefail

# Detect LAN IP: try common interfaces, fall back to the default route.
IP="$(ipconfig getifaddr en0 2>/dev/null || true)"
[ -z "$IP" ] && IP="$(ipconfig getifaddr en1 2>/dev/null || true)"
if [ -z "$IP" ]; then
  IP="$(route -n get default 2>/dev/null | awk '/interface:/{print $2}' \
        | xargs -I{} ipconfig getifaddr {} 2>/dev/null || true)"
fi

if [ -z "$IP" ]; then
  echo "Could not auto-detect a LAN IP. Are you connected to Wi-Fi?" >&2
  exit 1
fi

PORT="${PORT:-8000}"
URL="http://${IP}:${PORT}"

echo ""
echo "  📱  Open this on your iPhone (same Wi-Fi):  ${URL}"
echo "      If it can't connect, allow incoming connections for php/node in"
echo "      System Settings → Network → Firewall (or turn the firewall off)."
echo ""

# APP_URL is exported so Laravel generates absolute URLs against the LAN IP.
# VITE_DEV_HOST is read by vite.config.js to bind Vite + HMR to the LAN IP.
export APP_URL="$URL"
export VITE_DEV_HOST="$IP"

exec npx concurrently -c "#93c5fd,#c4b5fd,#fb7185,#fdba74" \
  "php artisan serve --host=0.0.0.0 --port=${PORT}" \
  "php artisan queue:listen --tries=1 --timeout=0" \
  "php artisan pail --timeout=0" \
  "npm run dev -- --host" \
  --names=server,queue,logs,vite --kill-others
