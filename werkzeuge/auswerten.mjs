import { zeilen } from './lies.mjs'
import { readdir } from 'fs/promises'

const ORDNER = '/home/rolf/DEV/abendlauf/user/pages/04.ranglisten'

const ZEIT = /^\d{1,3}:\d{2}[:.]\d$/          // 04:09:2  oder 24:31.5
const JAHR = /^(19|20)\d{2}$/

/** "04:09:2" -> Sekunden als Zahl */
const sekunden = z => {
  const [m, s, t] = z.split(/[:.]/).map(Number)
  return m * 60 + s + t / 10
}
const formatiert = sek => {
  const m = Math.floor(sek / 60)
  const s = Math.floor(sek % 60)
  const t = Math.round((sek % 1) * 10)
  return `${m}:${String(s).padStart(2, '0')},${t}`
}

/** Kategoriezeile: "1210 m A Piccolo Knaben 16-17" */
const KAT = /^(\d{3,5})\s*m\s+([A-Z])\s+(.+?)\s*(\d{2}\s*-\s*\d{2}|\d{4}\s*-\s*\d{4}|\+?\s*\d{2,4})?$/

/** Kategorienamen über die Jahre vergleichbar machen */
function normalisiere(name) {
  return name
    .replace(/\s*\d{2,4}\s*[-+]\s*\d{0,4}\s*$/, '')
    .replace(/[()]/g, '')
    .replace(/\s+/g, ' ')
    .trim()
    .replace(/^Schülerinnen Mädchen$/i, 'Schülerinnen')
    .replace(/^Schüler Knaben$/i, 'Schüler Knaben')
}

export async function leseDatei(pfad, jahr) {
  const eintraege = []
  let kat = null
  for (const { text } of await zeilen(pfad)) {
    const k = text.match(KAT)
    if (k) {
      kat = { distanz: Number(k[1]), kuerzel: k[2], name: normalisiere(k[3]) }
      continue
    }
    if (!kat || /^Rang\s+Nummer/.test(text)) continue

    const teile = text.split(' ').filter(Boolean)
    if (teile.length < 6 || !/^\d{1,4}$/.test(teile[0])) continue

    // Von rechts: alle Zeiten abschöpfen
    const zeitenRueck = []
    let i = teile.length - 1
    while (i >= 0 && ZEIT.test(teile[i])) { zeitenRueck.unshift(teile[i]); i-- }
    if (zeitenRueck.length < 2) continue          // mindestens ein Lauf + Gesamt

    const rest = teile.slice(0, i + 1)
    const jIdx = rest.findIndex(t => JAHR.test(t))
    if (jIdx < 3) continue

    const gesamt = zeitenRueck[zeitenRueck.length - 1]
    const laeufe = zeitenRueck.slice(0, -1)

    eintraege.push({
      jahr,
      kategorie: kat.name,
      kuerzel: kat.kuerzel,
      distanz: kat.distanz,
      rang: Number(rest[0]),
      nachname: rest[2],
      vorname: rest.slice(3, jIdx).join(' '),
      jahrgang: Number(rest[jIdx]),
      ort: rest.slice(jIdx + 1).join(' '),
      laeufe: laeufe.map(sekunden),
      gesamt: sekunden(gesamt),
    })
  }
  return eintraege
}

if (import.meta.url === `file://${process.argv[1]}`) {
  const dateien = (await readdir(ORDNER)).filter(f => /^\d{4}-gesamt\.pdf$/.test(f)).sort()
  const alle = []
  for (const d of dateien) {
    const jahr = Number(d.slice(0, 4))
    try {
      const e = await leseDatei(`${ORDNER}/${d}`, jahr)
      alle.push(...e)
      const kats = new Set(e.map(x => x.kategorie))
      console.log(`  ${jahr}  ${String(e.length).padStart(4)} Einträge, ${kats.size} Kategorien`)
    } catch (err) {
      console.log(`  ${jahr}  FEHLER: ${err.message}`)
    }
  }
  const { writeFile } = await import('fs/promises')
  await writeFile('daten.json', JSON.stringify(alle))
  console.log(`\n${alle.length} Einträge aus ${dateien.length} Gesamtranglisten`)
}
