# Urner Abendläufe — Website

Neubau von [abendlauf.ch](https://www.abendlauf.ch) mit
[Grav](https://getgrav.org) anstelle von WordPress.

## Voraussetzungen

**PHP 8.3 oder neuer** mit den Erweiterungen `curl`, `ctype`, `dom`,
`gd`, `json`, `mbstring`, `openssl`, `session`, `simplexml`, `xml`,
`zip`. Keine Datenbank — Grav legt die Inhalte als Dateien ab.

## Einrichten

Grav selbst liegt nicht im Repository. Nach dem Klonen einmal:

```bash
werkzeuge/grav-einrichten.sh
php bin/plugin login new-user -u <name> -e <mail> -P b --admin-type both
```

## Lokal starten

```bash
php -S localhost:8100 system/router.php
```

- Website: <http://localhost:8100>
- Panel: <http://localhost:8100/admin>

## Aufbau

| Ordner | Inhalt |
|---|---|
| `user/pages/` | Alle Inhalte als Markdown mit YAML-Kopf, plus Medien |
| `user/themes/abendlauf/blueprints/` | Was im Panel bearbeitbar ist |
| `user/themes/abendlauf/templates/` | Darstellung (Twig) |
| `user/themes/abendlauf/css,js,fonts,img` | Gestaltung und Schriften |
| `user/config/` | Konfiguration, auch die Vereinsangaben |
| `user/blueprints/config/site.yaml` | Erweitert das Panel um diese Angaben |
| `design/` | Gestaltungsentwürfe |
| `werkzeuge/` | Einrichtung und Datenimport |

Alle Farben und Schriftgrössen stehen als Tokens am Anfang von
`user/themes/abendlauf/css/main.css`. Wer das Erscheinungsbild ändern
will, ändert dort und nirgends sonst.

## Vor dem Livegang

1. **SMTP-Zugang eintragen** in `user/config/plugins/email.yaml` —
   die Platzhalter beginnen mit `REPLACE-`. Ohne das versendet das
   Kontaktformular nichts.
2. **PHP-Version prüfen:** Grav 2 braucht 8.3 oder neuer.
3. **Medien hochladen:** `user/pages/` per SFTP kopieren (siehe unten).
4. **Cache einschalten** in `user/config/system.yaml`.

## Nicht im Repository

- **Grav selbst** (`system/`, `vendor/`, `bin/`, `user/plugins/`) —
  rund 75 MB Fremdcode. Wird mit `werkzeuge/grav-einrichten.sh` geholt,
  dadurch bleibt ein Grav-Update ein Download statt ein Riesen-Commit.
- **Fotoalben** und **PDFs** — zusammen rund 120 MB.
- **Benutzerkonten** (`user/accounts/`) und der CSRF-Signaturschlüssel
  (`user/config/security-private.php`).
- **Generiertes** (`cache/`, `logs/`, `images/`, `assets/`).

### Medien wieder einspielen

1. **Beim Livegang:** `user/pages/` per SFTP auf den Server kopieren.
2. **Im Betrieb:** über das Panel hochladen — der normale Weg.
3. **Von der alten Seite holen:** `werkzeuge/import-fotos.py`.

Ab dem Livegang ist der Server die Quelle der Wahrheit für Inhalte,
nicht der Entwicklungsrechner.

## Warum Grav und nicht Kirby

Die erste Fassung lief mit Kirby (siehe Zweig `main`). Kirby kostet
einmalig CHF 95 pro Domain. Bei sechs Vereins- und Projektseiten
summiert sich das, deshalb der Wechsel auf Grav — quelloffen unter
MIT-Lizenz, ohne Lizenzkosten, gleiche Bauart: dateibasiert, ohne
Datenbank, mit Panel.

Übernommen wurden Gestaltung, Inhalte und Struktur unverändert. Neu
geschrieben wurden die Vorlagen, weil Grav Twig statt PHP nutzt.
