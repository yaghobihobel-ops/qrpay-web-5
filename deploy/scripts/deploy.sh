#!/usr/bin/env bash
set -euo pipefail

ENVIRONMENT=""
SHA=""

while [[ $# -gt 0 ]]; do
  case "$1" in
    --environment=*)
      ENVIRONMENT="${1#*=}"
      ;;
    --sha=*)
      SHA="${1#*=}"
      ;;
    *)
      echo "Unknown argument: $1" >&2
      exit 1
      ;;
  esac
  shift
done

if [[ -z "$ENVIRONMENT" || -z "$SHA" ]]; then
  echo "Usage: $0 --environment=<env> --sha=<commit>" >&2
  exit 2
fi

echo "[deploy] Starting deployment"
echo "Environment: $ENVIRONMENT"
echo "Commit: $SHA"

echo "Simulating deployment..."
sleep 1
echo "Deployment completed"
