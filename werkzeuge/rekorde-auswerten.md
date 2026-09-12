# Streckenrekorde aus den Ranglisten auswerten

Die Rekorde auf der Startseite stammen aus den Gesamtranglisten-PDFs
unter `user/pages/04.ranglisten/`. Die Gesamtrangliste enthält neben
der Summe auch die Einzelzeiten der drei Abende — daraus ergibt sich
die schnellste Einzelleistung.

## Wiederholen nach einem neuen Jahrgang

```bash
cd werkzeuge
npm install pdfjs-dist        # einmalig
node auswerten.mjs            # liest alle *-gesamt.pdf
```

Danach die Bestzeiten je Kategorie mit den Werten im Panel unter
*Startseite → Rekorde* vergleichen.

## Grenzen

- Auswertbar sind die Jahrgänge **2013 bis 2026**. Die Dateien von
  2008 bis 2012 und 2018 sind spaltenweise aufgebaut, mit je eigenem
  Layout; dort liesse sich jede Datei einzeln aufschlüsseln, der
  Aufwand steht aber in keinem Verhältnis.
- 2020 fand kein Anlass statt.
- Wer einen Abend verpasst hat, erscheint mit weniger Einzelzeiten.
  Welcher Abend fehlt, lässt sich der Gesamtrangliste nicht entnehmen —
  deshalb nennen die Rekorde nur Jahr und Zeit, nicht den Abend.
- Die Distanzen sind über alle ausgewerteten Jahre konstant
  (1210 / 2260 / 4440 / 6700 m). Die Rekorde sind also vergleichbar.
