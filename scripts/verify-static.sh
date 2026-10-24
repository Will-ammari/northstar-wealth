#!/usr/bin/env sh
set -eu
ROOT=$(CDPATH='' cd -- "$(dirname "$0")/.." && pwd)
find "$ROOT/backend" -name '*.php' -type f -print | while IFS= read -r file; do
  php -l "$file" >/dev/null
done
echo 'PASS: PHP syntax checks (not Laravel runtime tests)'
node "$ROOT/scripts/typescript-syntax.cjs"
