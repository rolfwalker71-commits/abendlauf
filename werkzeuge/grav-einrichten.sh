#!/usr/bin/env bash
# Holt Grav und legt es um den versionierten Teil dieses Projekts herum.
#
# Versioniert sind nur user/pages, user/themes, user/config und
# user/blueprints — also unsere eigenen Inhalte, Vorlagen und
# Einstellungen. Grav selbst samt Plugins kommt aus dem offiziellen
# Paket und bleibt austauschbar.
set -euo pipefail

cd "$(dirname "$0")/.."
ZIEL=$(pwd)
TEMP=$(mktemp -d)
trap 'rm -rf "$TEMP"' EXIT

echo "Lade Grav mit Admin-Panel …"
curl -fsSL "https://getgrav.org/download/core/grav-admin/latest" -o "$TEMP/grav.zip"
unzip -q "$TEMP/grav.zip" -d "$TEMP"

echo "Setze Grav ein, ohne eigene Dateien zu überschreiben …"
# -n = niemals Vorhandenes überschreiben; unsere user/-Dateien bleiben.
cp -rn "$TEMP/grav-admin/." "$ZIEL/"

# Gravs Beispielinhalte und -theme entfernen
rm -rf "$ZIEL/user/pages/01.home/default.md" "$ZIEL/user/pages/02.typography"
rm -f  "$ZIEL/README.md.grav"

echo "Setze Schreibrechte …"
for d in cache logs tmp backup images assets user/data user/accounts; do
  mkdir -p "$ZIEL/$d"; chmod -R 755 "$ZIEL/$d" 2>/dev/null || true
done

echo
echo "Fertig. Panel-Konto anlegen:"
echo "  php bin/plugin login new-user -u <name> -e <mail> -P b --admin-type both"
echo
echo "Lokal starten:"
echo "  php -S localhost:8100 system/router.php"
