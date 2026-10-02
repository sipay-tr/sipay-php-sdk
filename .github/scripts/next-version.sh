#!/usr/bin/env bash
# Reads git tags from stdin and prints the next vMAJOR.MINOR.PATCH version.
# Only plain semver tags (vX.Y.Z) are considered; starts from v0.0.0 if none exist.
#
# Usage: git tag --list 'v*' | next-version.sh <patch|minor|major>
set -euo pipefail

bump="${1:-}"
case "$bump" in
  patch | minor | major) ;;
  *)
    echo "Usage: $0 <patch|minor|major>" >&2
    exit 1
    ;;
esac

latest="$(grep -E '^v[0-9]+\.[0-9]+\.[0-9]+$' | sed 's/^v//' | sort -t. -k1,1n -k2,2n -k3,3n | tail -n 1 || true)"
IFS=. read -r major minor patch <<< "${latest:-0.0.0}"

case "$bump" in
  major) major=$((major + 1)); minor=0; patch=0 ;;
  minor) minor=$((minor + 1)); patch=0 ;;
  patch) patch=$((patch + 1)) ;;
esac

echo "v${major}.${minor}.${patch}"
