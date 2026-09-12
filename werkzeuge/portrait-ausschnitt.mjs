import sharp from '/home/rolf/DEV/abendlauf/_archiv-nextjs/node_modules/sharp/lib/index.js'

const ANALYSE = 240

/** Deutlich gesättigtes Orange — die Farbe der Helferleibchen. */
function istLeibchen(r, g, b) {
  return r > 140 && g > 0.25 * r && g < 0.62 * r && b < 0.40 * r
}

/**
 * Porträtausschnitt ohne Hautton-Erkennung.
 *
 * Diese Aufnahmen sind alle gleich aufgebaut: eine Person im orangen
 * Leibchen vor einer gleichmässigen Wand. Daraus lässt sich der Kopf
 * viel sicherer bestimmen als über die Hautfarbe, die bei warmem
 * Licht auch die Wand und graue Haare erfasst.
 *
 *  1. Wandfarbe aus den oberen Bildecken lesen
 *  2. Oberkante des Leibchens finden  → Schulterhöhe
 *  3. Oberhalb davon: alles, was von der Wand abweicht, ist der Kopf
 *  4. Ausschnitt um den Kopf legen, mit Luft darüber
 */
export async function ausschnitt(datei) {
  const { data, info } = await sharp(datei).rotate().resize({ width: ANALYSE })
    .removeAlpha().raw().toBuffer({ resolveWithObject: true })
  const { width: W, height: H, channels: C } = info
  const px = (x, y) => { const i = (y * W + x) * C; return [data[i], data[i+1], data[i+2]] }

  // 1. Wandfarbe: Median der oberen Ecken (je 12 % Breite, 10 % Höhe)
  const proben = []
  const randB = Math.max(2, Math.floor(W * 0.12))
  const randH = Math.max(2, Math.floor(H * 0.10))
  for (let y = 0; y < randH; y++) {
    for (let x = 0; x < randB; x++)            proben.push(px(x, y))
    for (let x = W - randB; x < W; x++)        proben.push(px(x, y))
  }
  const median = k => {
    const v = proben.map(p => p[k]).sort((a, b) => a - b)
    return v[Math.floor(v.length / 2)]
  }
  const wand = [median(0), median(1), median(2)]

  const abweichung = (x, y) => {
    const [r, g, b] = px(x, y)
    return Math.hypot(r - wand[0], g - wand[1], b - wand[2])
  }
  const SCHWELLE = 42

  // 2. Oberkante des Leibchens
  const orange = new Array(H).fill(0)
  for (let y = 0; y < H; y++)
    for (let x = 0; x < W; x++)
      if (istLeibchen(...px(x, y))) orange[y]++
  const orangeSpitze = Math.max(...orange)
  if (orangeSpitze < W * 0.08) return null
  const schulter = orange.findIndex(v => v >= orangeSpitze * 0.15)
  if (schulter < 4) return null

  // 3. Kopf: oberhalb der Schulter alles, was nicht Wand ist
  let kopfOben = -1
  const spalten = new Array(W).fill(0)
  for (let y = 0; y < schulter; y++) {
    let n = 0
    for (let x = 0; x < W; x++) {
      if (abweichung(x, y) < SCHWELLE) continue
      n++; spalten[x]++
    }
    if (kopfOben < 0 && n >= W * 0.035) kopfOben = y
  }
  if (kopfOben < 0) return null

  const spitzeS = Math.max(...spalten)
  const haltenS = spitzeS * 0.30
  const links  = spalten.findIndex(v => v >= haltenS)
  const rechts = W - 1 - [...spalten].reverse().findIndex(v => v >= haltenS)
  const mitteX = (links + rechts) / 2

  const kopfH = Math.max(6, schulter - kopfOben)

  // 4. Kopf soll etwa 55 % der Ausschnitthöhe einnehmen
  const seite = Math.min(kopfH / 0.55, W, H)
  const luft  = seite * 0.16                       // Luft über dem Kopf

  let x1 = Math.round(mitteX - seite / 2)
  let y1 = Math.round(kopfOben - luft)
  x1 = Math.max(0, Math.min(x1, W - seite))
  y1 = Math.max(0, Math.min(y1, H - seite))

  const meta = await sharp(datei).rotate().metadata()
  const f = meta.width / W
  const s = Math.round(seite * f)
  return {
    left:   Math.max(0, Math.min(Math.round(x1 * f), meta.width  - s)),
    top:    Math.max(0, Math.min(Math.round(y1 * f), meta.height - s)),
    width:  s,
    height: s,
    kopfAnteil: +(kopfH / seite).toFixed(2),
  }
}
