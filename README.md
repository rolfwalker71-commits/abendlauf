# Urner Abendläufe — Website

Neubau von [abendlauf.ch](https://www.abendlauf.ch) mit
[Kirby 5](https://getkirby.com) anstelle von WordPress.

## Voraussetzungen

PHP 8.2 oder neuer mit den Erweiterungen `mbstring`, `curl`, `gd`,
`intl`, `zip`, `dom`. Keine Datenbank — Kirby legt die Inhalte als
Dateien unter `content/` ab.

## Lokal starten

```bash
php -S localhost:8080 kirby/router.php
```

- Website: <http://localhost:8080>
- Backend: <http://localhost:8080/panel>

Beim ersten Aufruf des Panels wird ein Konto angelegt.

## Aufbau

| Ordner | Inhalt |
|---|---|
| `content/` | Alle Inhalte als Textdateien plus die zugehörigen Medien |
| `site/blueprints/` | Was im Backend bearbeitbar ist |
| `site/templates/` | Wie die Seiten dargestellt werden |
| `site/snippets/` | Wiederverwendete Bausteine (Kopfzeile, Kacheln, …) |
| `site/plugins/` | Eigene Erweiterungen |
| `assets/` | CSS, Javascript, Schriften, Logo |
| `kirby/` | Das CMS selbst — nicht verändern |
| `design/` | Gestaltungsentwürfe |
| `werkzeuge/` | Hilfsskripte für den Datenimport |

Alle Farben und Schriftgrössen stehen als Tokens am Anfang von
`assets/css/main.css`. Wer das Erscheinungsbild ändern will, ändert
dort und nirgends sonst.

## Nicht im Repository

Bewusst ausgeschlossen (siehe `.gitignore`):

- **Fotoalben** (`content/4_fotos/*/*.jpg`) und **Ranglisten-PDFs**
  (`content/3_ranglisten/*.pdf`) — zusammen rund 120 MB. Git eignet
  sich schlecht für Binärdateien; einmal eingecheckt bleiben sie für
  immer in der Versionsgeschichte.
- **Benutzerkonten** (`site/accounts/`), Sitzungen und die
  Kirby-Lizenzdatei.
- **Generierte Vorschaubilder** (`media/`) — Kirby erzeugt sie beim
  ersten Aufruf neu.

### Medien wieder einspielen

1. **Beim Livegang:** `content/` per SFTP auf den Server kopieren.
2. **Im Betrieb:** über das Panel hochladen — das ist der normale Weg.
3. **Von der alten Seite holen:** `werkzeuge/import-fotos.py`
   lädt die Alben direkt von abendlauf.ch.

Ab dem Livegang ist der Server die Quelle der Wahrheit für Inhalte,
nicht der Entwicklungsrechner.

## Lizenz

Kirby ist kostenpflichtig: einmalig 99 Euro pro Domain, sobald die
Seite öffentlich erreichbar ist. Lokal entwickeln ist kostenlos.
