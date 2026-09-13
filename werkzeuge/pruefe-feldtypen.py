#!/usr/bin/env python3
"""Vergleicht die gespeicherten Werte jeder Seite mit dem Feldtyp im Blueprint."""
import json, os, pathlib, sys, urllib.request, yaml

BASIS = pathlib.Path(__file__).resolve().parent.parent
TOK = (os.environ.get('GRAV_TOKEN') or '').strip()
if not TOK:
    sys.exit('Kein Token. So geht es:\n'
             '  export GRAV_TOKEN=$(curl -s -X POST http://localhost:8100/api/v1/auth/token \\\n'
             '    -H "Content-Type: application/json" \\\n'
             '    -d \'{"username":"NAME","password":"PASSWORT"}\' | python3 -c \'import sys,json;print(json.load(sys.stdin)["data"]["access_token"])\')')

def blueprint(vorlage):
    r = urllib.request.Request(
        f'http://localhost:8100/api/v1/blueprints/pages/{vorlage}',
        headers={'Authorization': f'Bearer {TOK}'})
    return json.load(urllib.request.urlopen(r))['data']['fields']

def felder(fs, pfad='', ziel=None):
    """Flache Abbildung Feldname -> Felddefinition."""
    ziel = {} if ziel is None else ziel
    for f in fs or []:
        t = f.get('type')
        if t in ('tab', 'tabs', 'section', 'fieldset', 'columns', 'column'):
            felder(f.get('fields'), pfad, ziel)
        elif t == 'list':
            ziel[f['name']] = f
            for u in (f.get('fields') or []):
                ziel[f['name'] + '|' + u['name'].lstrip('.')] = u
        elif f.get('name'):
            ziel[f['name']] = f
            felder(f.get('fields'), pfad, ziel)
    return ziel

SKALAR = {'text','textarea','email','tel','url','markdown','editor','select',
          'date','datetime','time','hidden','colorpicker','pagemediaselect'}

def pruefe(feld, wert, wo, raus):
    t = feld.get('type')
    mehrfach = feld.get('multiple') is True
    vt = feld.get('validate', {}).get('type')

    if t == 'pagemediaselect':
        if mehrfach:
            if wert is not None and not isinstance(wert, list):
                raus.append((wo, t, 'sollte eine Liste sein', repr(wert)))
        elif isinstance(wert, (list, dict)):
            raus.append((wo, t, 'Liste statt Text', repr(wert)))
    elif t in ('toggle','switch','checkbox') or vt == 'bool':
        if wert is not None and not isinstance(wert, bool):
            raus.append((wo, t, 'kein Ja/Nein-Wert', repr(wert)))
    elif t == 'number':
        if wert not in (None, '') and not isinstance(wert, (int, float)):
            raus.append((wo, t, 'keine Zahl', repr(wert)))
    elif t == 'list':
        if wert is not None and not isinstance(wert, list):
            raus.append((wo, t, 'keine Liste', repr(wert)[:60]))
    elif t == 'select' and not mehrfach:
        opt = feld.get('options')
        werte = None
        if isinstance(opt, list):
            werte = {o.get('value') for o in opt if isinstance(o, dict)}
        elif isinstance(opt, dict):
            werte = set(map(str, opt.keys()))
        if werte and wert not in (None, '') and str(wert) not in werte:
            raus.append((wo, t, f'nicht in der Auswahl {sorted(werte)}', repr(wert)))
    elif t in SKALAR:
        if isinstance(wert, (list, dict)):
            raus.append((wo, t, 'Liste statt Text', repr(wert)[:60]))

raus, ohne_feld = [], []
for md in sorted(BASIS.glob('user/pages/**/*.md')):
    text = md.read_text()
    if not text.startswith('---'):
        continue
    kopf = yaml.safe_load(text.split('---', 2)[1]) or {}
    vorlage = md.stem
    try:
        fs = felder(blueprint(vorlage))
    except Exception as e:
        print(f'  Blueprint {vorlage} nicht lesbar: {e}'); continue
    seite = str(md.relative_to(BASIS / 'user/pages'))

    for k, v in kopf.items():
        f = fs.get('header.' + k)
        if not f:
            if k not in ('title','media_order','routable','visible','published',
                         'process','cache_enable','slug','menu','template','taxonomy',
                         'date','metadata','http_response_code','body_classes','content',
                         'form'):   # technische Formulardefinition, bewusst nicht im Panel
                ohne_feld.append((seite, k))
            continue
        if f.get('type') == 'list' and isinstance(v, list):
            pruefe(f, v, f'{seite} · {k}', raus)
            for n, eintrag in enumerate(v, 1):
                if not isinstance(eintrag, dict): continue
                for uk, uv in eintrag.items():
                    uf = fs.get('header.' + k + '|' + uk)
                    if uf: pruefe(uf, uv, f'{seite} · {k}[{n}].{uk}', raus)
        else:
            pruefe(f, v, f'{seite} · {k}', raus)

if raus:
    print(f'{len(raus)} Feld(er) mit unpassendem Wert:\n')
    for wo, t, was, wert in raus:
        print(f'  {wo}\n      Feldtyp {t}: {was}\n      steht drin: {wert}\n')
else:
    print('Keine Typabweichungen gefunden.')
if ohne_feld:
    print(f'\n{len(ohne_feld)} Angabe(n) ohne Feld im Panel:')
    for s, k in ohne_feld: print(f'  {s} · {k}')
