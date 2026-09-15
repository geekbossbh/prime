#!/usr/bin/env bash
# Builds the plugin, publishes it into dist/, and writes the manifest the live
# site polls. Run this instead of handing anyone a zip.
set -euo pipefail
cd "$(dirname "$0")"

CORE=$(ls wordpress/lioness-prime/core/lioness-core-*.php | sort -V | tail -1)
VERSION=$(basename "$CORE" .php | sed 's/lioness-core-//')

./build-plugin.sh > /dev/null
SHA=$(sha256sum dist/lioness-prime.zip | cut -d' ' -f1)

cat > dist/update.json <<JSON
{
  "version": "$VERSION",
  "package": "https://raw.githubusercontent.com/geekbossbh/prime/refs/heads/claude/affectionate-bell-7uot8x/dist/lioness-prime.zip",
  "sha256": "$SHA",
  "url": "https://github.com/geekbossbh/prime",
  "requires_php": "7.4",
  "tested": "6.9"
}
JSON

echo "published $VERSION"
echo "  sha256 $SHA"
echo "  $(du -h dist/lioness-prime.zip | cut -f1) dist/lioness-prime.zip"
