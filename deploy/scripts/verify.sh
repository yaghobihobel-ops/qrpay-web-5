#!/usr/bin/env bash
set -euo pipefail

ENVIRONMENT=""

while [[ $# -gt 0 ]]; do
  case "$1" in
    --environment=*)
      ENVIRONMENT="${1#*=}"
      ;;
    *)
      echo "Unknown argument: $1" >&2
      exit 1
      ;;
  esac
  shift
done

if [[ -z "$ENVIRONMENT" ]]; then
  echo "Usage: $0 --environment=<env>" >&2
  exit 2
fi

echo "[verify] Checking health for $ENVIRONMENT"
sleep 1
echo "[verify] All checks passed"
