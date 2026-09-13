# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

Website der **Urner Abendläufe** (STV Altdorf), Neubau von abendlauf.ch auf **Grav 2.1** (flat-file CMS, keine Datenbank) mit Panel **Admin2** über das **API-Plugin**. Gepflegt von Rolf Walker (OK, Web/Informatik), der nicht programmiert: Alles, was auf der Website sichtbar ist – ausser berechneten Statistiken –, muss im Panel bearbeitbar sein. Sprache im Projekt (Code-Kommentare, Commits, Panel-Texte, Antworten): **Deutsch, Schweizer Schreibweise (ss statt ß)**.

## Befehle

```bash
werkzeuge/grav-einrichten.sh                # nach dem Klonen: Grav-Kern + Plugins holen (nicht versioniert)
php -S localhost:8100 system/router.php     # Entwicklungsserver; Panel unter /admin
rm -rf cache/twig cache/compiled            # nach Änderungen an Templates/Blueprints, wenn alte Ausgabe erscheint

php werkzeuge/pruefe-saison.php             # Regeln der Jahresautomatik (Saison.php), ohne Grav lauffähig
python3 werkzeuge/pruefe-backend.py         # jedes Inhaltsfeld hat ein Panel-Feld? (Exit 1 wenn nicht)
GRAV_TOKEN=… python3 werkzeuge/pruefe-feldtypen.py   # gespeicherte Werte passen zum Feldtyp? (braucht API-Token)
python3 werkzeuge/fotos-holen.py 2024       # Fotoalben eines Jahrgangs von der alten WordPress-Seite holen
(cd werkzeuge && npm install pdfjs-dist && node auswerten.mjs)   # Ranglisten-PDFs auslesen (lies.mjs), siehe rekorde-auswerten.md
```

`pruefe-feldtypen.py` und alle API-Aufrufe brauchen den laufenden Entwicklungsserver; `pruefe-saison.php` und `pruefe-backend.py` nicht.

API-Token für Prüfungen/Tests (lokales Konto):
`curl -s -X POST http://localhost:8100/api/v1/auth/token -H 'Content-Type: application/json' -d '{"username":"…","password":"…"}'` → `data.access_token`, als `Authorization: Bearer` senden. Blueprints: `GET /api/v1/blueprints/pages/<vorlage>`. Uploads/Speichern über die API lösen dieselben Ereignisse aus wie das Panel – so lassen sich Panel-Abläufe ohne Browser testen (Testdateien danach per `DELETE` wieder entfernen).

Nach einer Änderung mindestens alle Seiten auf HTTP 200 prüfen (`/ /ausschreibung /strecke /ranglisten /fotos /sponsoren /das-ok /kontakt /links /impressum /datenschutz`). Kontraste werden bei jeder Farbentscheidung nachgerechnet (WCAG: Text ≥ 4,5:1, Bedienelement-Konturen ≥ 3:1).

**Git:** Zweige `grav` (Arbeitszweig) und `main` werden identisch gehalten: auf `grav` committen, pushen, dann `main` per `git merge --ff-only grav` nachziehen und pushen. Das Repository ist **öffentlich**.

## Was versioniert ist – und was nicht

Versioniert sind nur `user/pages`, `user/themes/abendlauf`, `user/config`, `user/blueprints` und `werkzeuge/`. Nicht im Repository: Grav-Kern (`system/`, `vendor/`, `bin/`, `user/plugins/`), Fotos und PDFs (`user/pages/**/*.pdf`, Album-JPGs), Generiertes (`cache/`, `images/`, `user/data/`) und **sicherheitskritisch** `user/accounts/`, `user/config/security-private.php`, API-Schlüssel (`user/data/`). Ab dem Livegang ist der **Server die Quelle der Wahrheit für Inhalte**; ein Deployment darf `user/pages/` nie überschreiben.

## Architektur

### Seite ↔ Vorlage ↔ Blueprint
Der Dateiname der Inhaltsdatei bestimmt Template und Panel-Maske: `user/pages/02.ausschreibung/ausschreibung.md` → `templates/ausschreibung.html.twig` → `blueprints/ausschreibung.yaml`. Alle Blueprints erweitern Gravs Standard mit `extends@: {type: default, context: blueprints://pages}`. Deshalb darf **keine eigene Vorlage `default` heissen** (sie würde von allen anderen geerbt) – die einfache Seite heisst `seite`.

### Medien im Panel (wichtig, schon einmal schiefgegangen)
- `type: pagemedia` ist in Admin2 die **komplette Medienverwaltung** der Seite (Upload, Sortierung, Stift-Symbol für Metadaten) und ignoriert Label/`accept`. **Genau ein** solches Feld pro Seite, in einem eigenen Reiter, Feldname `header.media_order`.
- Gravs eigener Reiter „Inhalt" wird in jedem Blueprint mit einem `content`-Tab überschrieben, der `header.media_order` auf `hidden` setzt – sonst erscheinen die Dateien doppelt.
- Jedes Feld, das eine Datei **auswählt** (Logo, Portrait, Karte, Cover, Kopfbild …), ist `type: pagemediaselect` und speichert einen **String**. Ältere Inhalte hatten Listen; Templates lesen deshalb beide Formen: `(x is iterable ? x|first : x)`.
- Metadaten je Datei liegen in `<datei>.meta.yaml`; welche Felder das Stift-Symbol anbietet, steht in `user/config/plugins/api.yaml` (`titel`, `art`, `jahr`, `lauf`, `alt`, `bildtext`).

### Eine Quelle je Angabe
- **Laufabende** (Startseite, Reiter *Laufabende*) steuern: Countdown, Terminkacheln, Saisonjahr, Austragungsnummer im Kopf, Anlage der Fotoalben, Zuordnung neuer Ranglisten, Wechsel des Aktuell-Textes.
- **Kategorien** (Ausschreibung): Alter → Jahrgänge werden aus `kategorienjahr` berechnet; die Rekordkacheln holen ihr Alter über `kuerzel` von dort; Kennzahlen der Startseite ebenso.
- **Vereinsdaten** in `user/config/site.yaml` unter `verein` (Panel: Konfiguration → Site, erweitert durch `user/blueprints/config/site.yaml`). Erste Austragung **1994**, `ausgefallen` listet abgesagte Jahre (2020, Corona). Die Austragungsnummer rechnet ausschliesslich `partials/austragung.html.twig` (Jahr − 1994 + 1 − ausgefallene Jahre; 2026 = 32.).
- **Ranglisten** sind keine Unterseiten: alle PDFs hängen an `04.ranglisten`, `ranglisten.html.twig` baut aus `meta.jahr`/`meta.lauf` eine Matrix Jahr × Abend.
- **Rekorde** (Startseite) sind gepflegte Werte, keine Auswertung; Feld `gruppe` (maenner/frauen/knaben/maedchen) wählt Piktogramm und Farbe.

### Jahresautomatik (`abendlauf.php` + `classes/Saison.php`)
`Saison.php` enthält die Regeln ohne Grav-Abhängigkeit (getestet von `werkzeuge/pruefe-saison.php`); `abendlauf.php` hängt sie an Ereignisse. Admin2 speichert über das API-Plugin, daher `onApiMediaUploaded`/`onApiPageUpdated`, zusätzlich `onAdminAfterAddMedia`/`onAdminAfterSave` fürs klassische Admin.
- Neue PDF auf der Ranglisten-Seite ohne `.meta.yaml` → Jahr/Lauf aus dem Dateinamen, sonst aus dem Upload-Zeitpunkt relativ zu den Laufabenden (bis 7 Tage danach; am letzten Abend ist die zweite PDF die Gesamtrangliste). Vorhandene Metadaten werden nie überschrieben.
- Speichern der Startseite → fehlende Alben der Saison anlegen (je Abend + Siegerehrung, Fotograf vom jüngsten Album). **Leere Alben sind in allen Templates ausgefiltert.**
- Twig-Filter `platzhalter`: `{nummer} {jahr} {termine} {vorige_nummer} {naechste_nummer} {naechstes_jahr}` in den Aktuell-Texten. `aktuellnachher` löst `aktuelltext` am letzten Laufabend um 21 Uhr ab (oder zu `aktuellwechsel`).
- `base.html.twig` zeigt während der Saison drei Tage lang „Neu online: …" für neue Ranglisten, sofern kein Hinweisbanner von Hand gesetzt ist.
- Twig-Filter `externe_links` markiert fremde Links (neuer Tab, „Link"-Chip).

### Gestaltung
Alle Farben/Grössen als Tokens am Anfang von `css/main.css`, Hell/Dunkel über `prefers-color-scheme` **und** `[data-theme]` (beide Blöcke pflegen). Leitfarbe Logo-Cyan `--accent`; als Schrift immer `--accent-text`. Signalgelb `--signal` **nur als Fläche mit dunkler Schrift** (als Schrift auf Weiss 1,26:1) – z. B. Datumsplättchen. `--frauen` (Himbeer) nur für Rekord-/Kategoriekarten. Schriften Oswald/Source Sans 3 selbst gehostet (keine Google Fonts, DSGVO). Temporäre Mitteilung „In eigener Sache" mit Ablaufdatum `insachenbis`.

## Stolpersteine

- **Twig `merge`** nummeriert Integer-Schlüssel neu → Schlüssel mit Buchstaben-Präfix bauen (`'j' ~ jahr`). `{% set %}` in einer `for`-Schleife wirkt nicht nach aussen → `filter`/`map` verwenden. Kein `group`-Filter für Seitenlisten. `include()` liefert `Twig\Markup` → in PHP-Filtern erst `(string)` casten. `Medium::html()` hat keinen Lazy-Parameter → `.loading('lazy')`.
- **Das Panel formatiert Inhaltsdateien beim Speichern um** (Einrückung, `datetime` wird Unix-Zeitstempel). Inhaltsdateien daher per YAML parsen statt per Textersetzung ändern und vorher prüfen, ob sie sich seit dem letzten Lesen geändert haben.
- `system.yaml`: `timezone: Europe/Zurich`, `date.handler: date` (mit `intl` wurde `m` als Minute gelesen).
- Nach Änderungen an PHP-Klassen braucht OPcache des Dev-Servers ~2 s, bevor die neue Fassung greift.
- Lokal ist `upload_max_filesize` 2 MB – grössere Dateien (Ausschreibung 8,4 MB) scheitern nur hier.
- `pkill -f` mit Mustern, die auf die eigene Shell passen, beendet die eigene Sitzung.

## Handbuch

`docs/handbuch.html` ist das Betriebshandbuch für den Nutzer (Jahresablauf, Panel-Übersicht, Automatik, Hosting, Pflege per Claude, offene Punkte), als Claude-Artefakt veröffentlicht. Bei Änderungen an Abläufen, Panel oder offenen Punkten mitführen und dasselbe Artefakt aktualisieren (Artifact-Tool mit `url`, nicht neu anlegen).

## Betrieb

Ziel-Hosting voraussichtlich Hostpoint: PHP 8.3 mit gd, curl, zip, exif, fileinfo, intl, mbstring, OPcache; `upload_max_filesize` 32M, `post_max_size` 64M, `memory_limit` 256M; Apache mit `.htaccess`; SSH und Cron wünschenswert. Vor dem Livegang: SMTP-Zugang in `user/config/plugins/email.yaml` (Platzhalter `REPLACE-…`), Cache einschalten, Medien per SFTP hochladen. Für Pflege per Claude ist **grav-mcp** (`npx -y grav-mcp`, `GRAV_API_URL`, `GRAV_API_KEY`) vorgesehen – Schlüssel für einen eingeschränkten Benutzer, nie im Repository.
