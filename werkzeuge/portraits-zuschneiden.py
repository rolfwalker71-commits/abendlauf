#!/usr/bin/env python3
"""
Schneidet OK-Porträts einheitlich zu: Gesicht gleich gross, Augen auf
gleicher Höhe, Gesicht mittig – quadratisch 900 × 900 px.

Die Gesichter findet OpenCV (YuNet). Das Mass stammt von den Porträts,
die 2026 gut aussahen: Gesichtshöhe 43,6 % der Bildbreite, Augen auf
51 % der Höhe. Ist das Original zu eng fotografiert, wird der grösste
mögliche Ausschnitt genommen (dann ist das Gesicht etwas grösser).

Einmalig einrichten (nichts wird im System installiert):
    python3 -m pip install --target /tmp/ok-libs opencv-python-headless pillow

Zuschneiden – Dateiname im Ziel = Name des Originals + "-scaled.jpg":
    PYTHONPATH=/tmp/ok-libs python3 werkzeuge/portraits-zuschneiden.py \\
        ~/Downloads/THG09334.jpg --ziel user/pages/07.das-ok

Mit --probe wird nichts geschrieben, nur gemessen.
"""
import argparse
import os
import sys
import urllib.request
from pathlib import Path

GESICHT = 0.436    # Gesichtshöhe ÷ Kantenlänge des Ausschnitts
AUGEN = 0.51       # Augenhöhe ÷ Kantenlänge
GROESSE = 900      # Ausgabe in Pixeln
MODELL_URL = ('https://github.com/opencv/opencv_zoo/raw/main/models/'
              'face_detection_yunet/face_detection_yunet_2023mar.onnx')
MODELL = Path(os.environ.get('TMPDIR', '/tmp')) / 'face_detection_yunet_2023mar.onnx'

try:
    import cv2
    from PIL import Image
except ImportError:
    sys.exit('OpenCV oder Pillow fehlt – siehe Anleitung oben in dieser Datei.')


def gesicht(pfad: Path) -> dict:
    """Grösstes Gesicht: Höhe, Augenhöhe und Augenmitte in Originalpixeln."""
    bild = cv2.imread(str(pfad))
    if bild is None:
        raise ValueError(f'{pfad} ist kein lesbares Bild')
    h, w = bild.shape[:2]
    s = 800 / max(h, w)
    klein = cv2.resize(bild, (round(w * s), round(h * s)))
    erkennung = cv2.FaceDetectorYN.create(str(MODELL), '', (klein.shape[1], klein.shape[0]), 0.6)
    _, gesichter = erkennung.detect(klein)
    if gesichter is None:
        raise ValueError(f'{pfad.name}: kein Gesicht gefunden')
    g = max(gesichter, key=lambda r: r[2] * r[3]) / s
    return {
        'w': w, 'h': h, 'anzahl': len(gesichter),
        'hoehe': float(g[3]),
        'augen_y': float((g[5] + g[7]) / 2),
        'mitte_x': float((g[4] + g[6]) / 2),
    }


def main() -> None:
    teil = argparse.ArgumentParser(description=__doc__.split('\n\n')[0])
    teil.add_argument('bilder', nargs='+', type=Path, help='Originalfotos')
    teil.add_argument('--ziel', type=Path, default=Path('user/pages/07.das-ok'))
    teil.add_argument('--probe', action='store_true', help='nur messen, nichts schreiben')
    arg = teil.parse_args()

    if not MODELL.exists():
        print('Lade Gesichtserkennung (YuNet, 230 KB) …')
        urllib.request.urlretrieve(MODELL_URL, MODELL)

    for original in arg.bilder:
        g = gesicht(original)
        seite = min(g['hoehe'] / GESICHT, g['w'], g['h'])
        x0 = min(max(g['mitte_x'] - seite / 2, 0), g['w'] - seite)
        y0 = min(max(g['augen_y'] - AUGEN * seite, 0), g['h'] - seite)
        eng = seite < g['hoehe'] / GESICHT - 1
        ziel = arg.ziel / f'{original.stem.lower()}-scaled.jpg'
        hinweis = ' · Original eng fotografiert, grösster Ausschnitt' if eng else ''
        if g['anzahl'] > 1:
            hinweis += f" · {g['anzahl']} Gesichter, das grösste genommen"
        print(f'{original.name} → {ziel}{hinweis}')
        if not arg.probe:
            with Image.open(original) as bild:
                bild.convert('RGB') \
                    .crop((round(x0), round(y0), round(x0 + seite), round(y0 + seite))) \
                    .resize((GROESSE, GROESSE), Image.LANCZOS) \
                    .save(ziel, quality=90, optimize=True, progressive=True)


if __name__ == '__main__':
    main()
