#!/usr/bin/env bash
#
# dev-tunnel.sh — Local dev only.
#
# Exposes the local ddev backend through a Cloudflare quick tunnel
# (ddev share --provider=cloudflared), points the Expo dev build at the
# public tunnel URL, and starts Metro with a clean cache so the URL is
# inlined into the bundle. Tears the tunnel down on exit.
#
# Why: EXPO_PUBLIC_API_URL is baked into the JS bundle at build time. A
# public HTTPS tunnel avoids the LAN-IP / firewall / http issues of talking
# to ddev directly from a device. The quick-tunnel URL is random and changes
# every run, so it must be re-injected each time — which is what this does.
#
# Usage:  npm run tunnel     (from mobile/)
#
set -euo pipefail

MOBILE_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
ENV_FILE="$MOBILE_DIR/.env.local"
LOG_FILE="$(mktemp -t ddev-share.XXXXXX.log)"
SHARE_PID=""

cleanup() {
    echo ""
    echo "==> Shutting down Cloudflare tunnel..."
    if [[ -n "$SHARE_PID" ]]; then
        # Negative PID targets the whole process group (setsid'd below),
        # so cloudflared is stopped along with ddev share.
        kill -TERM "-$SHARE_PID" 2>/dev/null || kill -TERM "$SHARE_PID" 2>/dev/null || true
    fi
    # Belt-and-suspenders: kill the cloudflared child bound to OUR router port
    # (extracted from the log), in case it got reparented out of the group.
    if [[ -f "$LOG_FILE" ]]; then
        local port
        port="$(grep -oE 'cloudflared tunnel --url http://127\.0\.0\.1:[0-9]+' "$LOG_FILE" | grep -oE '[0-9]+$' | head -1 || true)"
        [[ -n "$port" ]] && pkill -TERM -f "cloudflared tunnel --url http://127.0.0.1:$port" 2>/dev/null || true
    fi
    rm -f "$LOG_FILE"
}
trap cleanup EXIT INT TERM

echo "==> Starting Cloudflare quick tunnel to local ddev..."
# setsid so the whole tunnel process group can be killed together on exit.
setsid ddev share --provider=cloudflared >"$LOG_FILE" 2>&1 &
SHARE_PID=$!

# Wait (up to ~40s) for the public trycloudflare.com URL to appear.
URL=""
for _ in $(seq 1 80); do
    if ! kill -0 "$SHARE_PID" 2>/dev/null; then
        echo "!! ddev share exited before publishing a URL. Output:" >&2
        cat "$LOG_FILE" >&2
        exit 1
    fi
    URL="$(grep -oE 'https://[a-zA-Z0-9.-]+\.trycloudflare\.com' "$LOG_FILE" | head -1 || true)"
    [[ -n "$URL" ]] && break
    sleep 0.5
done

if [[ -z "$URL" ]]; then
    echo "!! Timed out waiting for the tunnel URL. Output:" >&2
    cat "$LOG_FILE" >&2
    exit 1
fi

API_URL="$URL/api/v1"

# Replace EXPO_PUBLIC_API_URL in .env.local if present, else append it.
# Preserves any other vars in the file.
if [[ -f "$ENV_FILE" ]] && grep -q '^EXPO_PUBLIC_API_URL=' "$ENV_FILE"; then
    sed -i.bak "s#^EXPO_PUBLIC_API_URL=.*#EXPO_PUBLIC_API_URL=$API_URL#" "$ENV_FILE"
    rm -f "$ENV_FILE.bak"
else
    echo "EXPO_PUBLIC_API_URL=$API_URL" >>"$ENV_FILE"
fi

echo ""
echo "==> Tunnel live:  $URL"
echo "==> Wrote EXPO_PUBLIC_API_URL=$API_URL to mobile/.env.local"
echo "==> Starting Expo dev client (clean cache). Ctrl+C stops both Metro and the tunnel."
echo ""

cd "$MOBILE_DIR"
# Run expo directly (not `npm start`) so the LAN-IP prestart hook does not
# overwrite the tunnel URL we just wrote.
npx expo start --dev-client --clear
