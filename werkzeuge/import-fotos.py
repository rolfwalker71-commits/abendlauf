import json, re, pathlib, urllib.request, time, sys
from collections import defaultdict

S = '/tmp/claude-1000/-home-rolf-DEV-abendlauf/d4907f9d-f17c-4bcf-ba6b-1a67301a92a3/scratchpad'
galerien = json.load(open(f'{S}/galerien.json'))
basis = pathlib.Path('/home/rolf/DEV/abendlauf/content/4_fotos')

# Galerien nach Jahr gruppieren, Monat aus dem Bildpfad ableiten
nach_jahr = defaultdict(list)
for gid, bilder in galerien.items():
    m = re.search(r'uploads/(\d{4})/(\d{2})/', bilder[0])
    nach_jahr[int(m.group(1))].append({'gid': int(gid), 'monat': int(m.group(2)), 'bilder': bilder})

alben = []
for jahr, gruppen in nach_jahr.items():
    august   = sorted([g for g in gruppen if g['monat'] <= 8],  key=lambda g: g['gid'])
    september = sorted([g for g in gruppen if g['monat'] >= 9], key=lambda g: -len(g['bilder']))
    for i, g in enumerate(august):
        alben.append({**g, 'jahr': jahr, 'lauf': str(i + 1), 'titel': f"{i + 1}. Abend {jahr}"})
    for i, g in enumerate(september):
        if i == 0:
            alben.append({**g, 'jahr': jahr, 'lauf': '3', 'titel': f"3. Abend {jahr}"})
        else:
            alben.append({**g, 'jahr': jahr, 'lauf': 'gesamt', 'titel': f"Rangverkündigung {jahr}"})

alben.sort(key=lambda a: (-a['jahr'], {'1': 1, '2': 2, '3': 3, 'gesamt': 4}[a['lauf']]))

opener = urllib.request.build_opener()
opener.addheaders = [('User-Agent', 'Mozilla/5.0')]

gesamt_ok = gesamt_fehler = 0
for nr, album in enumerate(alben, start=1):
    slug = f"{album['jahr']}-{'rangverkuendigung' if album['lauf'] == 'gesamt' else album['lauf'] + '-abend'}"
    ordner = basis / f"{nr}_{slug}"
    ordner.mkdir(parents=True, exist_ok=True)

    (ordner / 'album.txt').write_text(
        f"Title: {album['titel']}\n\n----\n\n"
        f"Jahr: {album['jahr']}\n\n----\n\n"
        f"Lauf: {album['lauf']}\n\n----\n\n"
        "Datum: \n\n----\n\n"
        "Beschreibung: \n\n----\n\n"
        "Fotograf: \n\n----\n\n"
        "Cover: []\n", encoding='utf-8')

    ok = fehler = 0
    for i, url in enumerate(album['bilder']):
        name = re.sub(r'[^a-z0-9.]+', '-', url.rsplit('/', 1)[-1].lower()).strip('-')
        ziel = ordner / name
        if ziel.exists():
            ok += 1
            continue
        try:
            with opener.open(url, timeout=60) as r:
                ziel.write_bytes(r.read())
            (ordner / f"{name}.txt").write_text(
                f"Template: foto\n\n----\n\nSort: {i + 1}\n\n----\n\nBildtext: \n\n----\n\nAlt: \n",
                encoding='utf-8')
            ok += 1
        except Exception as e:
            fehler += 1
        if i % 25 == 0:
            print(f"  {album['titel']}: {i}/{len(album['bilder'])}", flush=True)
        time.sleep(0.03)

    mb = sum(p.stat().st_size for p in ordner.glob('*.jpg')) // 1024 // 1024
    print(f"FERTIG {album['titel']:28} {ok:4} Bilder, {fehler} Fehler, {mb} MB", flush=True)
    gesamt_ok += ok
    gesamt_fehler += fehler

print(f"\nIMPORT ABGESCHLOSSEN: {gesamt_ok} Bilder in {len(alben)} Alben, {gesamt_fehler} Fehler", flush=True)
