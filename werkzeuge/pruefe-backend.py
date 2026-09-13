#!/usr/bin/env python3
"""
Prüft, ob jedes Feld in den Inhalten auch im Panel bearbeitbar ist.

Grav hat keine Datenbank: Inhalte sind Markdown-Dateien mit YAML-Kopf,
die Eingabemasken stehen als Blueprints daneben. Ein Feld, das im
Inhalt vorkommt, aber in keinem Blueprint steht, lässt sich nur
direkt in der Datei ändern — genau das soll dieses Skript finden.

Aufruf aus dem Projektverzeichnis:  python3 werkzeuge/pruefe-backend.py
"""
import pathlib, re, sys
import yaml

VORLAGEN = {'home', 'ausschreibung', 'strecke', 'ranglisten', 'fotos', 'album',
            'sponsoren', 'ok', 'kontakt', 'default', 'error'}

# Von Grav selbst verwaltet, braucht kein eigenes Feld
SYSTEM = {'title', 'visible', 'published', 'date', 'slug', 'menu', 'taxonomy',
          'process', 'form', 'http_response_code', 'media_order', 'routable',
          'template', 'body_classes'}

BLUEPRINTS = pathlib.Path('user/themes/abendlauf/blueprints')


def felder(vorlage: str):
    p = BLUEPRINTS / f'{vorlage}.yaml'
    if not p.exists():
        return None
    return {m.lower() for m in re.findall(r'header\.([a-zA-Z_][\w]*)',
                                          p.read_text(encoding='utf-8'))}


offen = 0
for md in sorted(pathlib.Path('user/pages').rglob('*.md')):
    if md.stem not in VORLAGEN:
        continue
    kopf = yaml.safe_load(md.read_text(encoding='utf-8').split('---', 2)[1]) or {}
    erlaubt = felder(md.stem)
    pfad = md.relative_to('user/pages')
    if erlaubt is None:
        print(f'  {pfad}: kein Blueprint für „{md.stem}"')
        offen += 1
        continue
    fehlend = sorted(k for k in kopf if k.lower() not in erlaubt and k not in SYSTEM)
    zustand = 'vollständig' if not fehlend else 'FEHLT: ' + ', '.join(fehlend)
    print(f'  {str(pfad):34} {zustand}')
    offen += len(fehlend)

print(f'\n{offen} Felder ohne Eingabemöglichkeit im Panel')
sys.exit(1 if offen else 0)
