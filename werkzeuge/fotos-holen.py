#!/usr/bin/env python3
"""Holt die Fotoalben eines Jahrgangs von der alten WordPress-Seite.

    python3 werkzeuge/fotos-holen.py 2025

Liest die Galerien von abendlauf.ch/fotos/, ordnet sie über die
Überschriften den Laufabenden zu und legt sie als Grav-Seiten unter
user/pages/05.fotos/ ab. Vorhandene Bilder werden nicht neu geladen,
der Aufruf lässt sich also gefahrlos wiederholen.
"""
import html as HTML
import json
import pathlib
import re
import sys
import time
import urllib.request

BASIS = pathlib.Path(__file__).resolve().parent.parent
FOTOS = BASIS / 'user/pages/05.fotos'
QUELLE = 'https://www.abendlauf.ch/fotos/'

LAUF = {'1. Abend': '1', '2. Abend': '2', '3. Abend': '3', 'Siegerehrung': 'gesamt'}
SLUG = {'1': '1-abend', '2': '2-abend', '3': '3-abend', 'gesamt': 'siegerehrung'}
TITEL = {'1': '1. Abend', '2': '2. Abend', '3': '3. Abend', 'gesamt': 'Siegerehrung'}

lade = urllib.request.build_opener()
lade.addheaders = [('User-Agent', 'Mozilla/5.0')]


def seite():
    with lade.open(QUELLE, timeout=60) as r:
        return r.read().decode('utf-8', 'replace')


def galerien(quelltext, jahr):
    """Galerie-Nummer -> Laufschlüssel, aus der Reihenfolge im Quelltext."""
    ereignisse = []
    muster = r'(20\d\d)\s*(?:&#8211;|–)\s*(1\. Abend|2\. Abend|3\. Abend|Siegerehrung)'
    for m in re.finditer(muster, quelltext):
        ereignisse.append((m.start(), 'titel', (m.group(1), m.group(2))))
    for m in re.finditer(r'foogallery-gallery-(\d+)', quelltext):
        ereignisse.append((m.start(), 'galerie', m.group(1)))
    ereignisse.sort()

    zuordnung, offen = {}, None
    for _, art, wert in ereignisse:
        if art == 'titel':
            offen = wert
        elif offen and wert not in zuordnung:
            zuordnung[wert] = offen
            offen = None
    return {gid: LAUF[was] for gid, (j, was) in zuordnung.items() if j == str(jahr)}


def bilder(quelltext, gid):
    """Die Bild-Adressen einer Galerie, in der Reihenfolge der Seite."""
    anker = quelltext.find(f'foogallery-gallery-{gid}"')
    if anker < 0:
        anker = quelltext.find(f'foogallery-gallery-{gid}')
    naechste = [m.start() for m in re.finditer(r'foogallery-gallery-\d+"', quelltext)
                if m.start() > anker]
    ende = naechste[0] if naechste else len(quelltext)
    teil = quelltext[anker:ende]
    gefunden, gesehen = [], set()
    for m in re.finditer(r'https://www\.abendlauf\.ch/wp-content/uploads/[^"\'\s]+?\.(?:jpg|jpeg|png)', teil, re.I):
        u = HTML.unescape(m.group(0))
        if re.search(r'-\d+x\d+\.(jpg|jpeg|png)$', u, re.I):   # Thumbnails überspringen
            continue
        if '/uploads/cache/' in u:      # vom Galerie-Plugin erzeugte Vorschau
            continue
        if u not in gesehen:
            gesehen.add(u)
            gefunden.append(u)
    return gefunden


def dateiname(url):
    n = url.rsplit('/', 1)[-1].lower()
    return re.sub(r'[^a-z0-9.]+', '-', n).strip('-')


def naechste_nummer():
    hoechste = 0
    for p in FOTOS.iterdir():
        m = re.match(r'(\d+)\.', p.name)
        if p.is_dir() and m:
            hoechste = max(hoechste, int(m.group(1)))
    return hoechste + 1


def main():
    if len(sys.argv) != 2 or not sys.argv[1].isdigit():
        sys.exit(__doc__)
    jahr = int(sys.argv[1])

    quelltext = seite()
    zuordnung = galerien(quelltext, jahr)
    if not zuordnung:
        sys.exit(f'Keine Galerien für {jahr} auf {QUELLE} gefunden.')

    reihenfolge = {'1': 1, '2': 2, '3': 3, 'gesamt': 4}
    aufgaben = sorted(zuordnung.items(), key=lambda x: reihenfolge[x[1]])
    nummer = naechste_nummer()

    print(f'{len(aufgaben)} Alben für {jahr}:')
    for gid, lauf in aufgaben:
        print(f'  Galerie {gid} -> {TITEL[lauf]}')
    print()

    for gid, lauf in aufgaben:
        ordner = FOTOS / f'{nummer:02d}.{jahr}-{SLUG[lauf]}'
        vorhanden = [p for p in FOTOS.iterdir()
                     if p.is_dir() and p.name.endswith(f'{jahr}-{SLUG[lauf]}')]
        if vorhanden:
            ordner = vorhanden[0]
        else:
            nummer += 1
        ordner.mkdir(parents=True, exist_ok=True)

        md = ordner / 'album.md'
        if not md.exists():
            md.write_text(
                '---\n'
                f"title: '{TITEL[lauf]} {jahr}'\n"
                f'jahr: {jahr}\n'
                f"lauf: {lauf if lauf == 'gesamt' else lauf}\n"
                "datum: ''\n"
                "beschreibung: ''\n"
                "fotograf: ''\n"
                'cover: ""\n'
                '---\n', encoding='utf-8')

        adressen = bilder(quelltext, gid)
        neu = uebersprungen = fehler = 0
        for i, url in enumerate(adressen):
            ziel = ordner / dateiname(url)
            if ziel.exists():
                uebersprungen += 1
                continue
            try:
                with lade.open(url, timeout=90) as r:
                    ziel.write_bytes(r.read())
                neu += 1
            except Exception as e:
                fehler += 1
                print(f'    FEHLER {url}: {e}')
            if neu and neu % 25 == 0:
                print(f'    {TITEL[lauf]}: {i + 1}/{len(adressen)}', flush=True)
            time.sleep(0.03)

        mb = sum(p.stat().st_size for p in ordner.glob('*.jpg')) // 1024 // 1024
        print(f'{ordner.name:<26} {len(adressen):>4} Bilder '
              f'({neu} neu, {uebersprungen} schon da, {fehler} Fehler), {mb} MB', flush=True)

    print('\nFertig. Datum und Fotograf/in stehen im Panel unter Fotos -> Album -> Angaben.')


if __name__ == '__main__':
    main()
