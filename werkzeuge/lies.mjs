import { getDocument } from 'pdfjs-dist/legacy/build/pdf.mjs'
import { readFile } from 'fs/promises'

/** Liest ein PDF zeilenweise aus — Zeilen über die y-Position gruppiert. */
export async function zeilen(pfad) {
  const daten = new Uint8Array(await readFile(pfad))
  const doc = await getDocument({ data: daten, verbosity: 0 }).promise
  const alle = []
  for (let s = 1; s <= doc.numPages; s++) {
    const seite = await doc.getPage(s)
    const inhalt = await seite.getTextContent()
    const nachY = new Map()
    for (const el of inhalt.items) {
      if (!el.str) continue
      const y = Math.round(el.transform[5] * 2) / 2
      if (!nachY.has(y)) nachY.set(y, [])
      nachY.get(y).push({ x: el.transform[4], s: el.str })
    }
    const sortiert = [...nachY.entries()].sort((a, b) => b[0] - a[0])
    for (const [, teile] of sortiert) {
      const text = teile.sort((a, b) => a.x - b.x).map(t => t.s).join(' ')
        .replace(/\s+/g, ' ').trim()
      if (text) alle.push({ seite: s, text })
    }
  }
  return alle
}

if (import.meta.url === `file://${process.argv[1]}`) {
  const z = await zeilen(process.argv[2])
  const von = Number(process.argv[3] ?? 0), bis = Number(process.argv[4] ?? 40)
  console.log(`${z.length} Zeilen insgesamt\n`)
  for (const l of z.slice(von, bis)) console.log(`  [S${l.seite}] ${l.text}`)
}
