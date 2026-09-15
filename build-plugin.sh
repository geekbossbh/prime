#!/usr/bin/env bash
# Assembles wordpress/lioness-prime into dist/lioness-prime.zip, the file you
# upload under WP Admin → Plugins → Add New Plugin → Upload Plugin.
set -euo pipefail
cd "$(dirname "$0")"

OUT=dist
PLUGIN=lioness-prime
rm -rf "$OUT/$PLUGIN" "$OUT/$PLUGIN.zip"
mkdir -p "$OUT/$PLUGIN/page/assets"

cp wordpress/$PLUGIN/$PLUGIN.php "$OUT/$PLUGIN/"
cp index.html                    "$OUT/$PLUGIN/page/"
cp assets/*                      "$OUT/$PLUGIN/page/assets/"

( cd "$OUT" && zip -qr "$PLUGIN.zip" "$PLUGIN" -x '.*' )
rm -rf "$OUT/$PLUGIN"
echo "built $OUT/$PLUGIN.zip"
unzip -l "$OUT/$PLUGIN.zip"
