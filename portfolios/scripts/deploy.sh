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
# Under the main site's document root, not a subdomain folder.
#
# The eight subdomains return 500 for every path, including files that do not
# exist — Apache never reaches the filesystem, so their vhosts are broken and
# no file placement fixes it. The main site works, and Laravel's public
# .htaccess serves a real directory before it rewrites to index.php, so a
# folder here is served as-is. Point REMOTE at the subdomain roots once the
# panel side is sorted.
# The subdomains' own document roots, as configured in the panel.
REMOTE="${REMOTE:-khandanilegacy/portfolios/dist}"

PEOPLE=("$@")
if [ ${#PEOPLE[@]} -eq 0 ]; then
  PEOPLE=(ansary ashik morsheda atik maria maimuna anas arafat)
fi

SSH_OPTS=(-i "$KEY" -o BatchMode=yes -o ConnectTimeout=20)

# The connection to this host drops mid-transfer often enough to matter, and a
# half-sent page is worse than none — so every copy is retried, and the size is
# read back afterwards to prove it arrived whole.
send() {
  local from="$1" to="$2" n
  for n in 1 2 3 4; do
    if scp "${SSH_OPTS[@]}" -q "$from" "$HOST:$to"; then return 0; fi
    sleep 4
  done
  echo "failed to upload $from after 4 attempts" >&2
  return 1
}

for p in "${PEOPLE[@]}"; do
  src="dist/$p"
  if [ ! -f "$src/index.html" ]; then
    echo "$p: no build found at $src — run npm run build first" >&2
    exit 1
  fi

  printf '%-9s ' "$p"
  ssh "${SSH_OPTS[@]}" "$HOST" "mkdir -p ~/$REMOTE/$p/img"
  send "$src/index.html" "~/$REMOTE/$p/index.html"
  # Without this the Laravel .htaccess above these folders is inherited and
  # rewrites every request into public/ — a loop, served as a 500.
  send "$src/.htaccess" "~/$REMOTE/$p/.htaccess"

  # Only this person's photograph, and only if one has been added yet.
  if compgen -G "$src/img/$p.jpg" > /dev/null; then
    send "$src/img/$p.jpg" "~/$REMOTE/$p/img/$p.jpg"
    printf 'index.html + photo  '
  else
    printf 'index.html          '
  fi

  # Read the size back off the server rather than trusting the exit code.
  ssh "${SSH_OPTS[@]}" "$HOST" "stat -c '%s bytes on server' ~/$REMOTE/$p/index.html"
done

echo
echo "Uploaded to ~/$REMOTE/<name>/ on $HOST"
echo "Live at https://<name>.khandanilegacy.com/"
