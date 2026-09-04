#!/usr/bin/env bash
# Uploads the eight built portfolios to the StackCP package over SSH.
#
# Each one goes to ~/public_html/<name>/, which is where StackCP points a
# subdomain's document root. Creating the subdomain itself — the DNS record and
# the vhost — is panel work this cannot do; putting the files in place first
# means the site answers the moment that record exists.
#
#   bash scripts/deploy.sh            # all eight
#   bash scripts/deploy.sh maria atik # just those
set -euo pipefail

KEY="${SSH_KEY:-$HOME/.ssh/alwawah_stackcp}"
HOST="${SSH_HOST:-8bit.com.bd@ssh.gb.stackcp.com}"
REMOTE="public_html"

PEOPLE=("$@")
if [ ${#PEOPLE[@]} -eq 0 ]; then
  PEOPLE=(ansary ashik morsheda atik maria maimuna anas arafat)
fi

SSH_OPTS=(-i "$KEY" -o BatchMode=yes -o ConnectTimeout=20)

for p in "${PEOPLE[@]}"; do
  src="dist/$p"
  if [ ! -f "$src/index.html" ]; then
    echo "$p: no build found at $src — run npm run build first" >&2
    exit 1
  fi

  printf '%-9s ' "$p"
  ssh "${SSH_OPTS[@]}" "$HOST" "mkdir -p ~/$REMOTE/$p/img"
  scp "${SSH_OPTS[@]}" -q "$src/index.html" "$HOST:~/$REMOTE/$p/index.html"

  # Only this person's photograph, and only if one has been added yet.
  if compgen -G "$src/img/$p.jpg" > /dev/null; then
    scp "${SSH_OPTS[@]}" -q "$src/img/$p.jpg" "$HOST:~/$REMOTE/$p/img/$p.jpg"
    printf 'index.html + photo  '
  else
    printf 'index.html          '
  fi

  # Read the size back off the server rather than trusting the exit code.
  ssh "${SSH_OPTS[@]}" "$HOST" "stat -c '%s bytes on server' ~/$REMOTE/$p/index.html"
done

echo
echo "Uploaded to ~/$REMOTE/<name>/ on $HOST"
echo "Each subdomain still needs its DNS record and document root set to that folder."
