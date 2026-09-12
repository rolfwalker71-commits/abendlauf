#!/usr/bin/env python3
"""
Wandelt die Inhalte von Kirbys Textformat nach Gravs Markdown.

Kirby trennt Felder mit einer Zeile aus vier Bindestrichen und schreibt
"Feld: Wert". Grav nutzt einen YAML-Kopf zwischen zwei Zeilen aus drei
Bindestrichen, gefolgt vom Fliesstext.

Beziehungen zwischen Dateien: Kirby legt zu jeder Mediendatei eine
gleichnamige .txt daneben, Grav eine .meta.yaml.
"""
import re, shutil, sys
from pathlib import Path

QUELLE = Path('content')
ZIEL   = Path('user/pages')

# Kirby-Ordner  ->  (Grav-Ordner, Vorlage, Felder die in den Fliesstext wandern)
SEITEN = [
    ('home',              '01.home',          'home',          []),
    ('1_ausschreibung',   '02.ausschreibung', 'ausschreibung', []),
    ('2_strecke',         '03.strecke',       'strecke',       ['text']),
    ('3_ranglisten',      '04.ranglisten',    'ranglisten',    []),
    ('4_fotos',           '05.fotos',         'fotos',         []),
    ('5_sponsoren',       '06.sponsoren',     'sponsoren',     []),
    ('6_das-ok',          '07.das-ok',        'ok',            []),
    ('7_links',           '08.links',         'default',       ['text']),
    ('8_kontakt',         '09.kontakt',       'kontakt',       ['text']),
    ('datenschutz',       'datenschutz',      'default',       ['text']),
]

VORLAGEN = {'home','ausschreibung','strecke','ranglisten','fotos','album',
            'sponsoren','ok','kontakt','default'}


def lies_kirby(pfad: Path) -> dict:
    """Kirbys Textformat in ein Wörterbuch überführen."""
    felder = {}
    for block in re.split(r'\n----\s*\n', pfad.read_text(encoding='utf-8')):
        block = block.strip()
        if not block or ':' not in block:
            continue
        schluessel, _, wert = block.partition(':')
        felder[schluessel.strip().lower()] = wert.strip()
    return felder


def als_yaml(schluessel: str, wert: str) -> str:
    """Wert so ausgeben, dass YAML ihn wieder korrekt einliest."""
    if wert == '':
        return f'{schluessel}: ""'
    # Kirby speichert Listen und Strukturen bereits als YAML
    if wert.lstrip().startswith('-'):
        eingerueckt = '\n'.join('  ' + z if z.strip() else z for z in wert.split('\n'))
        return f'{schluessel}:\n{eingerueckt}'
    if '\n' in wert:
        eingerueckt = '\n'.join('  ' + z for z in wert.split('\n'))
        return f'{schluessel}: |\n{eingerueckt}'
    if wert.lower() in ('true','false') or re.fullmatch(r'-?\d+(\.\d+)?', wert):
        return f'{schluessel}: {wert}'
    return f'{schluessel}: {chr(39)}{wert.replace(chr(39), chr(39)*2)}{chr(39)}'


def wandle_seite(quelle: Path, ziel: Path, vorlage: str, in_text: list) -> int:
    ziel.mkdir(parents=True, exist_ok=True)

    seitendatei = next((p for p in quelle.glob('*.txt') if p.stem in VORLAGEN), None)
    if seitendatei is None:
        print(f'  {quelle}: keine Seitendatei gefunden', file=sys.stderr)
        return 0

    felder = lies_kirby(seitendatei)
    titel  = felder.pop('title', quelle.name)
    uuid   = felder.pop('uuid', None)
    koerper = '\n\n'.join(felder.pop(f) for f in in_text if felder.get(f))

    kopf = [f'title: {chr(39)}{titel}{chr(39)}']
    for k, v in felder.items():
        kopf.append(als_yaml(k, v))
    if uuid:
        kopf.append(f'# aus Kirby übernommen: {uuid}')

    (ziel / f'{vorlage}.md').write_text(
        '---\n' + '\n'.join(kopf) + '\n---\n\n' + koerper + ('\n' if koerper else ''),
        encoding='utf-8')

    # Medien samt Metadaten übernehmen
    anzahl = 0
    for datei in sorted(quelle.iterdir()):
        if datei.is_dir() or datei.suffix == '.txt':
            continue
        shutil.copy2(datei, ziel / datei.name)
        anzahl += 1
        meta = quelle / f'{datei.name}.txt'
        if meta.exists():
            m = lies_kirby(meta)
            m.pop('template', None)
            zeilen = [als_yaml(k, v) for k, v in m.items() if v]
            if zeilen:
                (ziel / f'{datei.name}.meta.yaml').write_text(
                    '\n'.join(zeilen) + '\n', encoding='utf-8')
    return anzahl


gesamt_seiten = gesamt_dateien = 0
for k_ordner, g_ordner, vorlage, in_text in SEITEN:
    quelle = QUELLE / k_ordner
    if not quelle.exists():
        print(f'  übersprungen (fehlt): {k_ordner}')
        continue
    n = wandle_seite(quelle, ZIEL / g_ordner, vorlage, in_text)
    gesamt_seiten += 1
    gesamt_dateien += n
    print(f'  {k_ordner:20} -> {g_ordner:18} {vorlage:14} {n:4} Dateien')

    # Alben als Unterseiten
    for unter in sorted(p for p in quelle.iterdir() if p.is_dir() and not p.name.startswith('_')):
        nummer = unter.name.split('_')[0]
        rest   = unter.name.split('_', 1)[1] if '_' in unter.name else unter.name
        n = wandle_seite(unter, ZIEL / g_ordner / f'{nummer.zfill(2)}.{rest}', 'album', [])
        gesamt_seiten += 1
        gesamt_dateien += n
        print(f'    {unter.name:18} -> {nummer.zfill(2)}.{rest:16} album      {n:4} Dateien')

print(f'\n{gesamt_seiten} Seiten, {gesamt_dateien} Mediendateien übernommen')
