#!/usr/bin/env bash
# push-to-main.sh
# Interactive script to bump version, update README and changed files, commit and push.
# Now supports:
#   - Push to origin/dev
#   - Merge dev -> main and push main
#   - Push directly to main (original behavior)
# On push rejection it will automatically fetch+rebase onto the remote branch and retry.
set -euo pipefail

# -------- Config (override with env vars if needed) --------
DEV_BRANCH="${DEV_BRANCH:-dev}"
MAIN_BRANCH="${MAIN_BRANCH:-main}"

# -------- Helpers --------
err() { echo "ERROR: $*" >&2; exit 1; }
info() { echo "INFO: $*"; }
prompt_yes_no() {
  local prompt="$1"
  local default="${2:-N}"
  local resp
  read -rp "$prompt " resp
  resp="${resp:-$default}"
  case "${resp,,}" in
    y|yes) return 0 ;;
    *) return 1 ;;
  esac
}

# Ensure we're in a git repo
if ! git rev-parse --is-inside-work-tree >/dev/null 2>&1; then
  err "This script must be run from inside a git repository."
fi

REPO_ROOT=$(git rev-parse --show-toplevel)
cd "$REPO_ROOT"

# -------- Detect uncommitted/untracked changes --------
git_status_porcelain=$(git status --porcelain)
if [[ -n "$git_status_porcelain" ]]; then
  echo "There are uncommitted changes in the working tree:"
  echo
  git status --short
  echo

  if prompt_yes_no "Would you like to commit these changes now? (y/N)" "N"; then
    if prompt_yes_no "Stage all changes (git add -A) and commit them now? (y/N)" "Y"; then
      git add -A
      read -rp "Enter commit message for local changes (default: 'chore: save local changes before release'): " local_commit_msg
      local_commit_msg=${local_commit_msg:-"chore: save local changes before release"}
      # Only commit if there is something staged
      if git diff --cached --quiet; then
        info "Nothing staged after git add -A (no changes to commit)."
      else
        git commit -m "$local_commit_msg"
        info "Committed local changes."
      fi
    else
      echo "You chose not to stage all changes automatically."
      if prompt_yes_no "Continue without committing? (y/N)" "N"; then
        info "Continuing without committing. Be aware uncommitted changes will remain."
      else
        err "Please commit or stash changes and re-run."
      fi
    fi
  else
    if prompt_yes_no "Continue without committing? (y/N)" "N"; then
      info "Continuing without committing. Be aware uncommitted changes will remain."
    else
      err "Please commit or stash changes and re-run."
    fi
  fi
fi

# -------- Version discovery helpers --------
get_version_from_package_json() {
  if [[ -f package.json ]]; then
    ver=$(sed -n 's/.*"version"[[:space:]]*:[[:space:]]*"\([^"]*\)".*/\1/p' package.json || true)
    [[ -n "$ver" ]] && echo "$ver" && return 0
  fi
  return 1
}
get_version_from_composer() {
  if [[ -f composer.json ]]; then
    ver=$(sed -n 's/.*"version"[[:space:]]*:[[:space:]]*"\([^"]*\)".*/\1/p' composer.json || true)
    [[ -n "$ver" ]] && echo "$ver" && return 0
  fi
  return 1
}
get_version_from_VERSION_file() {
  if [[ -f VERSION ]]; then
    tr -d ' \t\n\r' < VERSION || true
  fi
}
get_version_from_tags() {
  if git rev-parse --verify --quiet refs/tags >/dev/null; then
    latest_tag=$(git describe --tags --abbrev=0 2>/dev/null || true)
    if [[ -n "$latest_tag" ]]; then
      echo "${latest_tag#v}"
      return 0
    fi
  fi
  return 1
}

# -------- Determine current version --------
current_version=""
current_version=$(get_version_from_tags || true)
if [[ -z "$current_version" ]]; then
  current_version=$(get_version_from_package_json || true)
fi
if [[ -z "$current_version" ]]; then
  current_version=$(get_version_from_composer || true)
fi
if [[ -z "$current_version" ]]; then
  current_version=$(get_version_from_VERSION_file || true)
fi

if [[ -z "$current_version" ]]; then
  echo "Could not auto-detect a current version (no tags, no package.json/composer.json/VERSION)."
  read -rp "Enter current version to use as base (example 0.1.0). Leave blank to start from 0.0.0: " manual_ver
  if [[ -z "$manual_ver" ]]; then
    current_version="0.0.0"
  else
    current_version="$manual_ver"
  fi
fi

info "Current version detected: $current_version"

# -------- Ask how to bump --------
echo "Select how you want to increment the version:"
echo "  1) patch  (x.y.(z+1))"
echo "  2) minor  (x.(y+1).0)"
echo "  3) major  ((x+1).0.0)"
echo "  4) prerelease (append -rc.1, -beta.1 or custom suffix)"
echo "  5) custom  (enter explicit version)"
read -rp "Choice [1]: " choice
choice="${choice:-1}"

# parse semver components (best-effort)
IFS='.' read -r major minor patch <<< "$(echo "$current_version" | sed -E 's/^v?([0-9]+)\.([0-9]+)\.([0-9]+).*$/\1.\2.\3/')" || true
major=${major:-0}; minor=${minor:-0}; patch=${patch:-0}

new_version=""
case "$choice" in
  1)
    patch=$((patch + 1))
    new_version="${major}.${minor}.${patch}"
    ;;
  2)
    minor=$((minor + 1))
    patch=0
    new_version="${major}.${minor}.${patch}"
    ;;
  3)
    major=$((major + 1))
    minor=0; patch=0
    new_version="${major}.${minor}.${patch}"
    ;;
  4)
    read -rp "Enter prerelease suffix (example rc.1 or beta.1) or leave blank to use rc.1: " suffix
    suffix="${suffix:-rc.1}"
    base="${major}.${minor}.${patch}"
    new_version="${base}-${suffix}"
    ;;
  5)
    read -rp "Enter the exact version you want (example 1.5.0): " custom
    if [[ -z "$custom" ]]; then
      err "No version provided."
    fi
    new_version="$custom"
    ;;
  *)
    err "Invalid choice."
    ;;
esac

echo "Proposed new version: $new_version"
read -rp "Proceed with this version? (y/N) " confirm
if [[ "${confirm,,}" != "y" ]]; then
  err "Aborted by user."
fi

# -------- Compute files changed vs remote --------
changed_files=""
git fetch origin "$MAIN_BRANCH" --quiet 2>/dev/null || true
if git rev-parse --verify --quiet "origin/$MAIN_BRANCH" >/dev/null; then
  changed_files=$(git diff --name-only "origin/$MAIN_BRANCH"...HEAD)
else
  if git rev-parse --verify --quiet "$MAIN_BRANCH" >/dev/null; then
    changed_files=$(git diff --name-only "$MAIN_BRANCH"...HEAD)
  else
    changed_files=$(git status --porcelain | awk '{print $2}' | tr '\n' ' ')
  fi
fi

# Always include README files in the scan
readme_files=$(git ls-files 'README*' 2>/dev/null || true)
# Compose files to scan uniquely
files_to_scan=$(printf "%s\n%s\n" "$changed_files" "$readme_files" | sed '/^\s*$/d' | sort -u)

if [[ -z "$files_to_scan" ]]; then
  info "No changed files found and no README files present. Nothing to update for version strings."
else
  info "Files to scan for version strings:"
  echo "$files_to_scan" | sed 's/^/  - /'
fi

# -------- In-file version replacement helpers --------
replace_version_in_file() {
  local file="$1"
  local cur="$2"
  local new="$3"

  # Use perl for robust word-boundary matching and preserve leading 'v' if present
  if grep -q -E "v?${cur}" "$file"; then
    perl -0777 -pe "s/(?<![0-9A-Za-z_.-])v?${cur}(?![0-9A-Za-z_.-])/${new}/g" -i.bak "$file"
    rm -f "${file}.bak"
    return 0
  fi
  return 1
}

update_special_files() {
  local cur="$1"
  local new="$2"
  local updated=()

  if [[ -f package.json ]]; then
    if grep -q "\"version\"[[:space:]]*:[[:space:]]*\"" package.json; then
      sed -E -i.bak "s/\"version\"[[:space:]]*:[[:space:]]*\"[^\"]+\"/\"version\": \"${new}\"/" package.json
      rm -f package.json.bak
      updated+=("package.json")
    fi
  fi

  if [[ -f composer.json ]]; then
    if grep -q "\"version\"[[:space:]]*:[[:space:]]*\"" composer.json; then
      sed -E -i.bak "s/\"version\"[[:space:]]*:[[:space:]]*\"[^\"]+\"/\"version\": \"${new}\"/" composer.json
      rm -f composer.json.bak
      updated+=("composer.json")
    fi
  fi

  if [[ -f VERSION ]]; then
    echo -n "${new}" > VERSION
    updated+=("VERSION")
  fi

  if (( ${#updated[@]} )); then
    printf "%s\n" "${updated[@]}"
  fi
}

# -------- Update files with new version --------
updated_files=()
if [[ -n "$files_to_scan" ]]; then
  while IFS= read -r f; do
    [[ -z "$f" ]] && continue
    [[ -d "$f" ]] && continue
    if replace_version_in_file "$f" "$current_version" "$new_version"; then
      updated_files+=("$f")
    fi
  done <<< "$files_to_scan"
fi

while IFS= read -r f; do
  [[ -z "$f" ]] && continue
  updated_files+=("$f")
done < <(update_special_files "$current_version" "$new_version" || true)

# Deduplicate updated_files
if (( ${#updated_files[@]} )); then
  mapfile -t updated_files < <(printf "%s\n" "${updated_files[@]}" | sort -u)
  info "The following files were updated with new version:"
  for u in "${updated_files[@]}"; do echo "  - $u"; done
else
  info "No files contained the current version string; no in-file replacements made."
fi

# -------- Stage and commit the bump --------
if (( ${#updated_files[@]} )); then
  git add "${updated_files[@]}"
  info "Staged version updates."
else
  info "No file updates to stage."
fi

read -rp "Enter commit message for the version bump (default: \"chore(release): bump version to $new_version\"): " commit_msg
commit_msg=${commit_msg:-"chore(release): bump version to ${new_version}"}

if git diff --cached --quiet; then
  info "Nothing staged to commit."
else
  git commit -m "$commit_msg"
  info "Committed: $commit_msg"
fi

# -------- Git helpers for pushing and merging --------
ensure_remote_branch_exists() {
  local branch="$1"
  git fetch origin "$branch" --quiet 2>/dev/null || true
  git rev-parse --verify --quiet "origin/$branch" >/dev/null
}

switch_to_branch_tracking_remote() {
  local branch="$1"
  if git rev-parse --verify --quiet "$branch" >/dev/null; then
    git switch "$branch"
  else
    if ensure_remote_branch_exists "$branch"; then
      git switch -c "$branch" --track "origin/$branch"
    else
      # Create from current HEAD if remote does not exist
      git switch -c "$branch"
    fi
  fi
}

push_with_autorebase() {
  local target_branch="$1"
  info "Pushing HEAD to origin/${target_branch}..."
  if git push origin "HEAD:${target_branch}"; then
    info "Push to origin/${target_branch} succeeded."
    return 0
  fi
  info "Push rejected. Attempting automatic fetch + rebase onto origin/${target_branch}..."
  git fetch origin "${target_branch}" || true
  if git rev-parse --verify --quiet "origin/${target_branch}" >/dev/null; then
    if git rebase "origin/${target_branch}"; then
      info "Rebase successful; retrying push..."
      git push origin "HEAD:${target_branch}" && { info "Push successful after rebase."; return 0; }
      info "Push still failed after rebase."
      return 1
    else
      echo "Rebase failed. Resolve conflicts manually and retry."
      echo "To abort rebase: git rebase --abort"
      return 1
    fi
  else
    info "Remote origin/${target_branch} not found; retrying push without rebase..."
    git push origin "HEAD:${target_branch}"
  fi
}

merge_remote_into_main_and_push() {
  local remote_source_ref="$1"   # e.g., origin/dev
  local main_branch="$2"         # e.g., main
  local merge_msg="$3"           # merge commit message

  info "Preparing to merge ${remote_source_ref} into ${main_branch}..."

  git fetch origin "$main_branch" || true
  git fetch origin "${remote_source_ref#origin/}" || true

  # Ensure local main is present and up to date
  if git rev-parse --verify --quiet "$main_branch" >/dev/null; then
    git switch "$main_branch"
    if git rev-parse --verify --quiet "origin/$main_branch" >/dev/null; then
      git rebase "origin/$main_branch" || {
        echo "Rebase of $main_branch onto origin/$main_branch failed. Resolve and re-run."
        return 1
      }
    fi
  else
    if git rev-parse --verify --quiet "origin/$main_branch" >/dev/null; then
      git switch -c "$main_branch" --track "origin/$main_branch"
    else
      err "Neither local nor remote '$main_branch' exists. Cannot proceed."
    fi
  fi

  # Merge the remote-tracking source into main (creates a merge commit)
  if git merge --no-ff "$remote_source_ref" -m "$merge_msg"; then
    info "Merge successful."
  else
    echo "Merge failed due to conflicts. Resolve them, commit, then push manually."
    return 1
  fi

  # Push main
  info "Pushing ${main_branch} to origin/${main_branch}..."
  if git push origin "$main_branch"; then
    info "Pushed ${main_branch}."
    return 0
  else
    info "Push of ${main_branch} failed."
    return 1
  fi
}

create_and_push_tag_here() {
  local version="$1"
  local prefix=""
  # preserve 'v' prefix if repository has existing v-prefixed tags
  latest_tag=$(git describe --tags --abbrev=0 2>/dev/null || true)
  if [[ "$latest_tag" =~ ^v[0-9] ]]; then
    prefix="v"
  fi
  local tag="${prefix}${version}"
  if git rev-parse --verify --quiet "refs/tags/${tag}" >/dev/null; then
    info "Tag ${tag} already exists locally; skipping create."
  else
    git tag -a "${tag}" -m "Release ${version}"
    info "Created tag ${tag} at $(git rev-parse --short HEAD)"
  fi
  git push origin --tags || true
  info "Pushed tags."
}

# -------- Choose workflow --------
echo
echo "Select release workflow:"
echo "  1) Push HEAD to origin/${DEV_BRANCH}"
echo "  2) Merge ${DEV_BRANCH} -> ${MAIN_BRANCH} and push ${MAIN_BRANCH} (will first push HEAD to origin/${DEV_BRANCH})"
echo "  3) Push HEAD directly to origin/${MAIN_BRANCH} (original behavior)"
echo "  4) Push HEAD to origin/(current branch)"
read -rp "Choice [2]: " wf
wf="${wf:-2}"

current_branch=$(git rev-parse --abbrev-ref HEAD)
case "$wf" in
  1)
    # Push to dev only
    if push_with_autorebase "$DEV_BRANCH"; then
      if prompt_yes_no "Create annotated tag ${new_version} on current HEAD (dev flow)? (y/N)" "N"; then
        create_and_push_tag_here "$new_version"
      fi
      info "Done. Pushed to origin/${DEV_BRANCH}. New version: ${new_version}"
    else
      err "Push to origin/${DEV_BRANCH} failed."
    fi
    ;;
  2)
    # Release via dev -> main
    info "Step 1/2: Push HEAD to origin/${DEV_BRANCH} (so main merges the latest dev state)..."
    push_with_autorebase "$DEV_BRANCH"

    info "Step 2/2: Merge origin/${DEV_BRANCH} into ${MAIN_BRANCH} and push ${MAIN_BRANCH}..."
    merge_msg="Merge ${DEV_BRANCH} into ${MAIN_BRANCH}: release ${new_version}"
    if merge_remote_into_main_and_push "origin/${DEV_BRANCH}" "$MAIN_BRANCH" "$merge_msg"; then
      if prompt_yes_no "Create annotated tag ${new_version} on ${MAIN_BRANCH} HEAD? (y/N)" "N"; then
        # We are currently on main (merge_remote_into_main_and_push switches)
        create_and_push_tag_here "$new_version"
      fi
      info "Done. ${DEV_BRANCH} merged into ${MAIN_BRANCH}. New version: ${new_version}"
    else
      err "Merge or push of ${MAIN_BRANCH} failed. Resolve and re-run."
    fi
    ;;
  3)
    # Direct push to main (existing behavior generalized)
    if push_with_autorebase "$MAIN_BRANCH"; then
      if prompt_yes_no "Create annotated tag ${new_version} on ${MAIN_BRANCH} HEAD? (y/N)" "N"; then
        create_and_push_tag_here "$new_version"
      fi
      info "Done. Pushed HEAD to origin/${MAIN_BRANCH}. New version: ${new_version}"
    else
      err "Push to origin/${MAIN_BRANCH} failed."
    fi
    ;;
  4)
    # Push to current branch
    if push_with_autorebase "$current_branch"; then
      if prompt_yes_no "Create annotated tag ${new_version} on current HEAD? (y/N)" "N"; then
        create_and_push_tag_here "$new_version"
      fi
      info "Done. Pushed HEAD to origin/${current_branch}. New version: ${new_version}"
    else
      err "Push to origin/${current_branch} failed."
    fi
    ;;
  *)
    err "Invalid workflow choice."
    ;;
esac

info "All done."
