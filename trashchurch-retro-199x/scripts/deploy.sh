#!/usr/bin/env bash
# deploy.sh — rsync deploy for trashchurch-retro-199x theme
# Usage:
#   ./deploy.sh           -> do a real deploy
#   ./deploy.sh --dry-run -> show what would change (rsync -n)
#   ./deploy.sh --help    -> show help
set -euo pipefail

# Resolve paths so this script can be run from anywhere when placed in the repo
SCRIPT_DIR="$(cd ""$(dirname ""){BASH_SOURCE[0]}"" && pwd)"
SRC="$(cd "$SCRIPT_DIR/.." && pwd)"   # copy contents of theme root
DEST="/var/www/html/wordpress/wp-content/themes/trashchurch-retro-199x/"
EXCLUDES=( ".git" ".github" )
CHOWN="www-data:www-data"
CHMOD="D775,F664"   # directories 775, files 664
DRY_RUN=false

# Parse args
for arg in "$@"; do
  case "$arg" in
    --dry-run|-n) DRY_RUN=true ;;
    --help|-h)
      cat <<EOF
Usage: $0 [--dry-run]
  --dry-run  : perform rsync with -n (no changes) so you can preview
  --help     : this message
EOF
      exit 0
      ;;
    *) echo "Unknown arg: $arg"; exit 2 ;; 
  esac
done

# Build rsync options
RSYNC_OPTS=( -a -v --delete --chown="$CHOWN" --chmod="$CHMOD" --human-readable --progress )
for e in "${EXCLUDES[@]}"; do
  RSYNC_OPTS+=( --exclude="$e" )
done
if [ "$DRY_RUN" = true ]; then
  RSYNC_OPTS+=( -n )
fi

# Use sudo if not running as root (chown will require privileges)
SUDO=""
if [ "$(id -u)" -ne 0 ]; then
  if command -v sudo >/dev/null 2>&1; then
    SUDO="sudo"
    echo "Not running as root; will prefix rsync with sudo for file ownership changes."
  else
    echo "Warning: not running as root and sudo not found — chown may fail."
  fi
fi

# Show command to be executed
echo
echo "SRC:  $SRC"
echo "DEST: $DEST"
echo "RSYNC command:"
echo "  \"${SUDO}\" rsync \"${RSYNC_OPTS[*]}\" \"$SRC\" \"$DEST\""
echo

# Confirm for non-dry runs (small safety)
if [ "$DRY_RUN" = false ]; then
  read -r -p "Proceed with deploy? [y/N] " answer
  case "$answer" in
    y|Y) ;; 
    *) echo "Aborted."; exit 1 ;;
  esac
fi

# Run rsync
${SUDO} rsync "${RSYNC_OPTS[@]}" "$SRC" "$DEST"

# If system uses SELinux, restore contexts (no-op on non-SELinux systems)
if command -v restorecon >/dev/null 2>&1; then
  echo "Restoring SELinux context on $DEST (restorecon -R)..."
  ${SUDO} restorecon -R "$DEST" || true
fi

echo "Deploy completed."

# End of script
