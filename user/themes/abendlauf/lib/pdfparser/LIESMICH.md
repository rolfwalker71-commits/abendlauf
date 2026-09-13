# smalot/pdfparser

Liest den Text der Ranglisten-PDFs, damit die Ranglisten-Seite das Podest
je Abend zeigen kann (siehe `classes/Rangliste.php`).

- Version **2.12.5**, unverändert übernommen aus
  <https://github.com/smalot/pdfparser> – nur `src/` und die Lizenz.
- Lizenz **LGPL-3.0** (`LICENSE.txt`).
- Reines PHP, braucht `iconv`, `zlib` und `mbstring` (bei Grav vorhanden).
  Deshalb funktioniert das Auslesen auch beim Hoster, ohne Node oder
  Zusatzprogramme.

Geladen wird die Bibliothek in `abendlauf.php` (`pdfBibliothek()`), und nur
dann, wenn eine Rangliste tatsächlich ausgelesen werden muss.

Aktualisieren: in einem leeren Ordner `composer require smalot/pdfparser`,
danach `vendor/smalot/pdfparser/src` hierher kopieren.
