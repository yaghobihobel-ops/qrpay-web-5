#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "$0")/.." && pwd)"
SPEC_FILE="$ROOT_DIR/public/docs/openapi/qrpay-api-1.0.0.yaml"
DIST_DIR="$ROOT_DIR/sdk/dist"
GENERATOR_IMAGE="openapitools/openapi-generator-cli:v7.5.0"

mkdir -p "$DIST_DIR"

run_generator() {
  local lang="$1"
  local output="$DIST_DIR/$lang"
  local generator="$2"
  
  echo "Generating $lang SDK..."
  rm -rf "$output"
  docker run --rm \
    -v "$ROOT_DIR:/local" \
    "$GENERATOR_IMAGE" generate \
    -i "/local${SPEC_FILE#${ROOT_DIR}}" \
    -g "$generator" \
    -o "/local/sdk/dist/$lang" \
    -c "/local/sdk/openapi-generator-$lang.yaml"
}

run_generator "typescript" "typescript-fetch"
run_generator "python" "python"
run_generator "php" "php"

echo "SDK generation complete. Artifacts are stored in $DIST_DIR."
