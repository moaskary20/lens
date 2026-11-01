#!/usr/bin/env bash
# Sync vendors to Pricing Models on internal + production Lens servers.
# Usage: ./scripts/sync-vendor-pricing-remote.sh
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
CMD="$ROOT/app/Console/Commands/SyncVendorPricing.php"

if [[ ! -f "$CMD" ]]; then
  echo "Missing $CMD" >&2
  exit 1
fi

PW="$(python3 - <<'PY'
from pathlib import Path
text = Path.home().joinpath('.ssh/config').read_text()
host = None
for line in text.splitlines():
    s = line.strip()
    if s.startswith('Host '):
        host = s.split(None, 1)[1]
    if host == 'estammar-server' and s.startswith('# Password:'):
        print(s.split(':', 1)[1].strip())
        break
PY
)"

ASK="$(mktemp)"
chmod 700 "$ASK"
printf '%s\n' '#!/bin/sh' "printf '%s\\n' \"$PW\"" >"$ASK"
trap 'rm -f "$ASK"' EXIT

ask_ssh() {
  local host="$1"; shift
  DISPLAY= SSH_ASKPASS_REQUIRE=force SSH_ASKPASS="$ASK" setsid -w ssh \
    -o StrictHostKeyChecking=no \
    -o PreferredAuthentications=password \
    -o PubkeyAuthentication=no \
    -o NumberOfPasswordPrompts=1 \
    -o ConnectTimeout=25 \
    "root@$host" "$@"
}

ask_scp() {
  local src="$1" host="$2" dest="$3"
  DISPLAY= SSH_ASKPASS_REQUIRE=force SSH_ASKPASS="$ASK" setsid -w scp \
    -o StrictHostKeyChecking=no \
    -o PreferredAuthentications=password \
    -o PubkeyAuthentication=no \
    -o NumberOfPasswordPrompts=1 \
    "$src" "root@${host}:${dest}"
}

sync_host() {
  local label="$1" host="$2" mode="$3"
  echo "== ${label} (${host}) =="
  if [[ "$mode" == "key" ]]; then
    scp -o BatchMode=yes -o ConnectTimeout=25 -i "$HOME/.ssh/id_ed25519" \
      "$CMD" "root@${host}:/tmp/SyncVendorPricing.php"
    ssh -o BatchMode=yes -o ConnectTimeout=25 -i "$HOME/.ssh/id_ed25519" "root@$host" \
      'cp /tmp/SyncVendorPricing.php /var/www/lens/app/Console/Commands/SyncVendorPricing.php && cd /var/www/lens && php artisan lens:sync-vendor-pricing'
  else
    ask_scp "$CMD" "$host" /tmp/SyncVendorPricing.php
    ask_ssh "$host" \
      'cp /tmp/SyncVendorPricing.php /var/www/lens/app/Console/Commands/SyncVendorPricing.php && cd /var/www/lens && php artisan lens:sync-vendor-pricing'
  fi
}

# Internal marketplace VPS
sync_host "internal" "38.242.251.149" "password"
# Public production
sync_host "production" "62.171.137.138" "key"

echo "Done."
