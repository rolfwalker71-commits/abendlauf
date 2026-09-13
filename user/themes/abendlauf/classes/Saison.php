<?php

namespace Grav\Theme\Abendlauf;

/**
 * Was sich jedes Jahr wiederholt, ohne dass jemand tippen muss.
 *
 * Bewusst ohne Abhängigkeit zu Grav, damit sich die Regeln auf der
 * Kommandozeile prüfen lassen (werkzeuge/pruefe-saison.php).
 */
final class Saison
{
    /** Titel, wie sie in Ranglisten und Alben erscheinen. */
    public const TITEL = ['1' => '1. Abend', '2' => '2. Abend', '3' => '3. Abend', 'gesamt' => 'Gesamtrangliste'];

    /**
     * Ordnet eine hochgeladene Rangliste Jahr und Lauf zu.
     *
     * Reihenfolge:
     *  1. Dateiname  – „2027-2-abend.pdf", „Rangliste Lauf 2 2027.pdf",
     *                  „Gesamtrangliste_2027.pdf"
     *  2. Kalender   – wann hochgeladen wurde, gemessen an den Laufabenden
     *                  der Startseite. Am Abend des 2. Laufs hochgeladen
     *                  heisst: 2. Lauf.
     *  3. Nichts     – dann bleibt es beim Stift-Symbol im Panel.
     *
     * Die Gesamtrangliste lässt sich am Kalender nicht vom 3. Abend
     * unterscheiden – beide entstehen am selben Abend. Steht „gesamt"
     * nicht im Namen, gilt deshalb: Liegt für den 3. Abend schon eine
     * Rangliste vor, ist die nächste die Gesamtrangliste.
     *
     * @param string   $dateiname  Name der hochgeladenen Datei
     * @param string[] $abende     Laufabende als 'Y-m-d' oder 'Y-m-d H:i'
     * @param int      $jetzt      Zeitpunkt des Hochladens
     * @param array    $vorhanden  bereits zugeordnete Ranglisten: [['jahr' => 2027, 'lauf' => '3'], …]
     * @return array{jahr:int, lauf:string, titel:string, art:string, quelle:string}|null
     */
    public static function rangliste(string $dateiname, array $abende, int $jetzt, array $vorhanden = []): ?array
    {
        $name = strtolower(pathinfo($dateiname, PATHINFO_FILENAME));
        $name = strtr($name, ['ä' => 'ae', 'ö' => 'oe', 'ü' => 'ue']);
        $name = ' ' . trim(preg_replace('/[^a-z0-9]+/', ' ', $name)) . ' ';

        $jahr = preg_match('/ ((?:19|20)\d{2}) /', $name, $m) ? (int) $m[1] : null;
        $gesamt = str_contains($name, 'gesamt');
        $lauf = null;
        if (!$gesamt) {
            if (preg_match('/ (?:lauf|abend)(?: nr)? ([123]) /', $name, $m)
                || preg_match('/ ([123]) (?:lauf|abend) /', $name, $m)) {
                $lauf = $m[1];
            }
        }

        if ($jahr !== null && ($gesamt || $lauf !== null)) {
            return self::ergebnis($jahr, $gesamt ? 'gesamt' : $lauf, 'Dateiname');
        }

        // Kalender: der letzte Laufabend, der schon begonnen hat und
        // höchstens eine Woche zurückliegt.
        $abend = self::laufenderAbend($abende, $jetzt);
        if ($abend === null) {
            return null;
        }
        [$abendJahr, $nummer, $letzte] = $abend;
        if ($jahr !== null && $jahr !== $abendJahr) {
            return null;   // Name nennt ein anderes Jahr – nicht raten
        }

        if (!$gesamt && $lauf === null && $nummer === $letzte) {
            foreach ($vorhanden as $v) {
                if ((int) ($v['jahr'] ?? 0) === $abendJahr && (string) ($v['lauf'] ?? '') === (string) $letzte) {
                    $gesamt = true;
                    break;
                }
            }
        }

        return self::ergebnis($abendJahr, $gesamt ? 'gesamt' : ($lauf ?? (string) $nummer), 'Kalender');
    }

    /**
     * Die Alben, die es für eine Saison geben soll: ein Album pro Laufabend
     * und eines für die Siegerehrung am letzten Abend.
     *
     * @param string[] $abende Laufabende als 'Y-m-d' oder 'Y-m-d H:i'
     * @return array<int, array{slug:string, titel:string, jahr:int, lauf:string, datum:string}>
     */
    public static function alben(array $abende): array
    {
        $nachJahr = [];
        foreach ($abende as $a) {
            $ts = strtotime((string) $a);
            if ($ts !== false) {
                $nachJahr[(int) date('Y', $ts)][] = $ts;
            }
        }

        $alben = [];
        foreach ($nachJahr as $jahr => $daten) {
            sort($daten);
            foreach ($daten as $i => $ts) {
                $nr = (string) ($i + 1);
                $alben[] = ['slug' => "$jahr-$nr-abend", 'titel' => "$nr. Abend $jahr", 'jahr' => $jahr, 'lauf' => $nr, 'datum' => date('Y-m-d', $ts)];
            }
            $alben[] = ['slug' => "$jahr-siegerehrung", 'titel' => "Siegerehrung $jahr", 'jahr' => $jahr, 'lauf' => 'gesamt', 'datum' => date('Y-m-d', end($daten))];
        }
        return $alben;
    }

    /**
     * Ersetzt Platzhalter in redaktionellen Texten, damit derselbe Text
     * Jahr für Jahr stimmt: {jahr}, {nummer}, {vorige_nummer},
     * {naechstes_jahr}, {naechste_nummer} und {termine} („18.8. / 25.8. / 1.9.").
     *
     * @param string[] $abende Laufabende als 'Y-m-d' oder 'Y-m-d H:i'
     */
    public static function platzhalter(string $text, int $jahr, int $nummer, array $abende = []): string
    {
        $tage = [];
        foreach ($abende as $a) {
            $ts = strtotime(substr((string) $a, 0, 10));
            if ($ts !== false && (int) date('Y', $ts) === $jahr) {
                $tage[] = $ts;
            }
        }
        sort($tage);
        return strtr($text, [
            '{jahr}' => (string) $jahr,
            '{nummer}' => (string) $nummer,
            '{vorige_nummer}' => (string) ($nummer - 1),
            '{naechstes_jahr}' => (string) ($jahr + 1),
            '{naechste_nummer}' => (string) ($nummer + 1),
            '{termine}' => implode(' / ', array_map(static fn ($t) => date('j.n.', $t), $tage)),
        ]);
    }

    /** @return array{0:int,1:int,2:int}|null  [Jahr, Nummer des Abends, Nummer des letzten Abends] */
    private static function laufenderAbend(array $abende, int $jetzt): ?array
    {
        $nachJahr = [];
        foreach ($abende as $a) {
            $ts = strtotime(substr((string) $a, 0, 10));
            if ($ts !== false) {
                $nachJahr[(int) date('Y', $ts)][] = $ts;
            }
        }
        $treffer = null;
        foreach ($nachJahr as $jahr => $daten) {
            sort($daten);
            foreach ($daten as $i => $tag) {
                if ($jetzt >= $tag && $jetzt < $tag + 7 * 86400) {
                    $treffer = [$jahr, $i + 1, count($daten)];
                }
            }
        }
        return $treffer;
    }

    private static function ergebnis(int $jahr, string $lauf, string $quelle): array
    {
        return ['jahr' => $jahr, 'lauf' => $lauf, 'titel' => self::TITEL[$lauf], 'art' => 'rangliste', 'quelle' => $quelle];
    }
}
