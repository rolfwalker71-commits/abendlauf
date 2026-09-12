/**
 * Kategorienamen über 18 Jahre vergleichbar machen.
 * Die Ranglisten nutzen wechselnde Schreibweisen und hängen den
 * Jahrgang an ("Volksläufer 95+älter", "Schülerinnen 01–").
 */
export function kanonisch(name) {
  let n = name
    // Jahrgangs-Anhängsel in allen gesehenen Varianten
    .replace(/\s*\d{2,4}\s*[+–-]\s*(älter|jünger)?\s*$/i, '')
    .replace(/\s*\d{2,4}\s*[–-]\s*\d{2,4}\s*$/, '')
    .replace(/\s+\d{2,4}\s*$/, '')
    .replace(/[()]/g, '')
    .replace(/\s+/g, ' ')
    .trim()

  const s = n.toLowerCase()
  // Schreibvarianten derselben Kategorie zusammenführen
  if (/pf(ü|ue)deri/.test(s))                 return 'Eltern mit Pfüderi'
  if (/h(ö|oe)seler/.test(s))                 return 'Eltern mit Höseler'
  if (/(kind\/eltern|eltern\/kind|kind mit eltern|eltern mit kind)/.test(s)) return 'Kind mit Eltern'
  if (/^kids? (knaben|jungen)/.test(s))       return 'Kids Knaben'
  if (/^kids? (m(ä|ae)dchen)/.test(s))        return 'Kids Mädchen'
  if (/^piccolo (knaben|jungen)/.test(s))     return 'Piccolo Knaben'
  if (/^piccolo m(ä|ae)dchen/.test(s))        return 'Piccolo Mädchen'
  if (/^sch(ü|ue)lerinnen/.test(s))           return 'Schülerinnen'
  if (/^sch(ü|ue)ler/.test(s))                return 'Schüler Knaben'
  if (/^jugend (knaben|jungen)/.test(s))      return 'Jugend Knaben'
  if (/^jugend m(ä|ae)dchen/.test(s))         return 'Jugend Mädchen'
  if (/^volksl(ä|ae)uferinnen/.test(s))       return 'Volksläuferinnen'
  if (/^volksl(ä|ae)ufer/.test(s))            return 'Volksläufer Männer'
  return n
}
