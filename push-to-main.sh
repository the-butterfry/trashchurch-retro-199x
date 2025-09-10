#!/usr/bin/env bash
# Generic helper to stage/commit local changes and push them to origin/main.
# Usage:
#   ./push-to-main.sh                # interactive (asks before pushing)
#   ./push-to-main.sh -m "msg" -y    # use msg and auto-confirm
#   ./push-to-main.sh -m "msg" -f    # force push (use with caution)
#
# Notes:
# - Safe by default: tries fast-forward first, then rebase. If either step
#   encounters conflicts you'll need to resolve them manually.
# - If origin/main is branch-protected, the push will be rejected. Create a PR instead.

set -euo pipefail

REMOTE="origin"
TARGET_BRANCH="main"
AUTO_YES=0
FORCE_PUSH=0
COMMIT_MSG=""
DRY_RUN=0

usage() {
  cat <<EOF
Usage: $0 [-m "commit message"] [-y] [-f] [--dry-run]
  -m "msg"   : Commit message to use (default: timestamped message)
  -y         : Auto confirm push (don't prompt)
  -f         : Force push (git push -f)
  --dry-run  : Print actions but don't actually push
EOF
  exit 1
}

while [[ $# -gt 0 ]]; do
  case "$1" in
    -m) shift; COMMIT_MSG="$1"; shift ;;
    -y) AUTO_YES=1; shift ;;
    -f) FORCE_PUSH=1; shift ;;
    --dry-run) DRY_RUN=1; shift ;;
    -h|--help) usage ;;
    *) echo "Unknown arg: $1"; usage ;;
  esac
done

# Helpers
err() { echo "ERROR: $*" >&2; exit 1; }
info() { echo "=> $*"; }

# Ensure we're inside a git repo
git rev-parse --is-inside-work-tree >/dev/null 2>&1 || err "Not inside a git repository."

# Remember original branch
ORIG_BRANCH="$(git symbolic-ref --short HEAD 2>/dev/null || git rev-parse --short HEAD)"
info "Current branch: $ORIG_BRANCH"

# Stage everything (including new/untracked files)
info "Staging all changes..."
git add -A

# Prepare commit message
if [[ -z "$COMMIT_MSG" ]]; then
  COMMIT_MSG="Save local changes: $(date -u +"%Y-%m-%dT%H:%M:%SZ")"
fi

# Commit if there are staged changes
if git diff --cached --quiet; then
  info "No staged changes to commit."
else
  info "Committing: $COMMIT_MSG"
  git commit -m "$COMMIT_MSG"
fi

# Fetch remote
info "Fetching $REMOTE..."
git fetch "$REMOTE"

# Ensure we have local TARGET_BRANCH; if not, try to create from remote
if git show-ref --verify --quiet "refs/heads/$TARGET_BRANCH"; then
  :
else
  if git ls-remote --exit-code --heads "$REMOTE" "$TARGET_BRANCH" >/dev/null 2>&1; then
    info "Creating local $TARGET_BRANCH from $REMOTE/$TARGET_BRANCH"
    git checkout -b "$TARGET_BRANCH" "$REMOTE/$TARGET_BRANCH"
  else
    info "Creating new local $TARGET_BRANCH (no remote branch exists)"
    git checkout -b "$TARGET_BRANCH"
  fi
fi

# Checkout target branch
info "Checking out $TARGET_BRANCH..."
git checkout "$TARGET_BRANCH"

# Attempt to update local main: prefer fast-forward, then rebase
info "Updating $TARGET_BRANCH from $REMOTE/$TARGET_BRANCH..."
set +e
git pull --ff-only "$REMOTE" "$TARGET_BRANCH"
FF_OK=$?
if [[ $FF_OK -ne 0 ]]; then
  info "Fast-forward failed; trying pull --rebase..."
  git pull --rebase "$REMOTE" "$TARGET_BRANCH"
  REBASE_OK=$?
  if [[ $REBASE_OK -ne 0 ]]; then
    err "Unable to update $TARGET_BRANCH automatically (rebase failed). Resolve manually and re-run."
  fi
fi
set -e

# If we started on a different branch, merge those commits into main
if [[ "$ORIG_BRANCH" != "$TARGET_BRANCH" ]]; then
  # If ORIG_BRANCH is an orphan/detached head, skip automatic merge
  if git rev-parse --verify "$ORIG_BRANCH" >/dev/null 2>&1; then
    info "Merging commits from $ORIG_BRANCH into $TARGET_BRANCH..."
    set +e
    git merge --no-ff --no-edit "$ORIG_BRANCH"
    MERGE_OK=$?
    if [[ $MERGE_OK -ne 0 ]]; then
      err "Merge conflict while merging $ORIG_BRANCH into $TARGET_BRANCH. Resolve conflicts and then run 'git push $REMOTE $TARGET_BRANCH'."
    fi
    set -e
  else
    info "Original branch '$ORIG_BRANCH' not available for merge (detached or unknown); skipping merge."
  fi
else
  info "Already on $TARGET_BRANCH; no branch merge needed."
fi

# Prompt before pushing
echo
info "About to push to $REMOTE/$TARGET_BRANCH"
if [[ $FORCE_PUSH -eq 1 ]]; then
  info "Force push enabled!"
fi

if [[ $DRY_RUN -eq 1 ]]; then
  info "[DRY RUN] Skipping actual push."
  echo "Run without --dry-run to perform the push."
  exit 0
fi

if [[ $AUTO_YES -ne 1 ]]; then
  read -r -p "Proceed with git push to $REMOTE/$TARGET_BRANCH? [y/N] " REPLY
  case "$REPLY" in
    [Yy]|[Yy][Ee][Ss]) ;;
    *) info "Aborted by user."; exit 0 ;;
  esac
fi

# Push
if [[ $FORCE_PUSH -eq 1 ]]; then
  git push --force "$REMOTE" "$TARGET_BRANCH"
else
  git push "$REMOTE" "$TARGET_BRANCH"
fi

info "Push complete. Latest commit on $TARGET_BRANCH:"
git --no-pager log -1 --pretty=format:'%h %s (%an) %ad' --date=local

# End
