#!/usr/bin/env bash
# push-to-main.sh
# Interactive script to bump version, update READMEs and changed files, commit and push.
# Usage: ./push-to-main.sh
set -euo pipefail

# Helpers
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

# Detect uncommitted/untracked changes
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

# Try to determine current version
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

# Ask how to bump
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
    read -rp "Enter the exact version you want (example 1.2.3): " custom
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

# Find changed files relative to origin/main (if available) or relative to main branch
changed_files=""
git fetch origin main --quiet 2>/dev/null || true
if git rev-parse --verify --quiet origin/main >/dev/null; then
  changed_files=$(git diff --name-only origin/main...HEAD)
else
  if git rev-parse --verify --quiet main >/dev/null; then
    changed_files=$(git diff --name-only main...HEAD)
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

# Function to replace version in file (attempt safe in-place edit)
replace_version_in_file() {
  local file="$1"
  local cur="$2"
  local new="$3"

  if grep -q -E "v?${cur}" "$file"; then
    perl -0777 -pe "s/(?<![0-9A-Za-z_.-])v?${cur}(?![0-9A-Za-z_.-])/${new}/g" -i.bak "$file"
    rm -f "${file}.bak"
    return 0
  fi
  return 1
}

# Also update package.json/composer.json/VERSION explicitly if present
update_special_files() {
  local cur="$1"
  local new="$2"
  local updated=()

  if [[ -f package.json ]]; then
    if grep -q '"version"[[:space:]]*:[[:space:]]*"' package.json"; then
      sed -E -i.bak "s/\"version\"[[:space:]]*:[[:space:]]*\"[^\"]+\"/\"version\": \"${new}\"/" package.json
      rm -f package.json.bak
      updated+=("package.json")
    fi
  fi
  if [[ -f composer.json ]]; then
    if grep -q '"version"[[:space:]]*:[[:space:]]*"' composer.json"; then
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

# Update files
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

# Update package.json/composer.json/VERSION if present even if not changed files
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

# If README files exist, scan for occurrences of the new version and, if none were found & no updates occurred, attempt to update
if [[ -z "${updated_files[*]-}" ]]; then
  info "Attempting to update README files for version mentions."
  for r in $readme_files; do
    if replace_version_in_file "$r" "$current_version" "$new_version"; then
      updated_files+=("$r")
    fi
  done
fi

# Stage changes
if (( ${#updated_files[@]} )); then
  git add "${updated_files[@]}"
  info "Staged version updates."
else
  info "No file updates to stage."
fi

# Commit
read -rp "Enter commit message for the version bump (default: \"chore(release): bump version to $new_version\"): " commit_msg
commit_msg=${commit_msg:-"chore(release): bump version to ${new_version}"}

if git diff --cached --quiet; then
  info "Nothing staged to commit."
else
  git commit -m "$commit_msg"
  info "Committed: $commit_msg"
fi

# Optionally create annotated tag
if prompt_yes_no "Create an annotated git tag for ${new_version}? (y/N)" "N"; then
  tag_prefix=""
  latest_tag=$(git describe --tags --abbrev=0 2>/dev/null || true)
  if [[ "$latest_tag" =~ ^v[0-9] ]]; then
    tag_prefix="v"
  fi
  git tag -a "${tag_prefix}${new_version}" -m "Release ${new_version}"
  info "Created tag ${tag_prefix}${new_version}"
fi

# Push
current_branch=$(git rev-parse --abbrev-ref HEAD)
echo "You are on branch: $current_branch"
if prompt_yes_no "Push to origin/main (will push HEAD to main)? (y/N)" "N"; then
  git push origin "HEAD:main"
  info "Pushed HEAD to origin/main"
  if git tag --list | grep -q "${new_version}" || git tag --list | grep -q "v${new_version}"; then
    git push origin --tags
    info "Pushed tags"
  fi
else
  if prompt_yes_no "Push to origin/$current_branch instead? (y/N)" "N"; then
    git push origin "$current_branch"
    info "Pushed origin/$current_branch"
    if git tag --list | grep -q "${new_version}" || git tag --list | grep -q "v${new_version}"; then
      git push origin --tags
      info "Pushed tags"
    fi
  else
    info "Skipping push. Local repo updated and committed. Remember to push when ready."
  fi
fi

info "Done. New version: $new_version"
