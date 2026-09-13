<?php

namespace Grav\Theme\Abendlauf;

/**
 * Liest den Text einer Ranglisten-PDF der Zeitmessung und liefert je
 * Kategorie die ersten Plätze. Ohne Grav-Abhängigkeit, geprüft von
 * werkzeuge/pruefe-rangliste.php.
 *
 * Aufbau jeder Seite:
 *   32. Urner Abendläufe 2026
 *   Rangliste pro Lauf Lauf Nr: 1 Datum: 19.08.2026   (oder „Gesamtrangliste Datum: …")
 *   1210 m A Piccolo Knaben 16-17
 *   Rang Nummer Nachname Vorname Jahrgang Ort Zeit …
 *   1 612 Nachname Vorname 2016 Altdorf UR 04:35:7 - 03:48:9 15.8
 *
 * In der Gesamtrangliste folgen auf den Ort die Zeiten der Abende, die
 * letzte ist die Summe. Zeilen ohne Rang (ausser Konkurrenz) zählen nicht.
 * Wiederholt sich eine Kategorie auf der nächsten Seite, geht die Liste
 * dort weiter.
 */
class Rangliste
{
    /** Steigt, wenn sich die gelieferten Felder ändern – ältere Zwischenspeicher gelten dann nicht mehr. */
    public const FORMAT = 2;

    private const ZEIT = '\d{1,3}:\d{2}[:.]\d';

    /**
     * @return array{datum: ?string, kategorien: list<array{kuerzel: string, distanz: int, name: string, plaetze: list<array{rang: int, name: string, ort: string, zeit: string}>}>}
     */
    public static function lesen(string $text, int $plaetze = 3): array
    {
        $datum = null;
        $kategorien = [];
        $aktuell = null;

        foreach (preg_split('/\R/u', $text) ?: [] as $zeile) {
            $zeile = trim((string) preg_replace('/\s+/u', ' ', $zeile));
            if ($zeile === '') {
                continue;
            }
            if ($datum === null && preg_match('/Datum: ?(\d{1,2})\.(\d{1,2})\.(\d{4})/u', $zeile, $d)) {
                $datum = sprintf('%04d-%02d-%02d', $d[3], $d[2], $d[1]);
                continue;
            }
            if (preg_match('/^(\d{3,5}) ?m ([A-Z]{1,2}) (.+)$/u', $zeile, $k)) {
                $aktuell = $k[2] . '-' . $k[1];
                $kategorien[$aktuell] ??= [
                    'kuerzel' => $k[2],
                    'distanz' => (int) $k[1],
                    'name'    => self::ohneAlter($k[3]),
                    'plaetze' => [],
                ];
                continue;
            }
            // Rang, Startnummer, Name, Jahrgang, Ort, danach die Zeit(en).
            // Der Ort ist „so kurz wie möglich" (??): fehlt er, wird nicht die erste Zeit zum Ort.
            if ($aktuell !== null
                && preg_match('/^(\d{1,4}) \d{1,5} (.+?) (?:19|20)\d{2} (?:(.*?) )??((?:' . self::ZEIT . ' ?)+)(?: |$)/u', $zeile, $e)
                && (int) $e[1] <= $plaetze
            ) {
                preg_match_all('/' . self::ZEIT . '/', $e[4], $zeiten);
                $kategorien[$aktuell]['plaetze'][] = [
                    'rang' => (int) $e[1],
                    'name' => $e[2],
                    'ort'  => trim($e[3]),
                    'zeit' => self::zeit((string) end($zeiten[0])),
                ];
            }
        }

        return ['datum' => $datum, 'kategorien' => array_values($kategorien)];
    }

    /** „04:35:7" → „4:35,7" – wie die Rekorde auf der Startseite */
    public static function zeit(string $roh): string
    {
        return preg_match('/^0*(\d+):(\d{2})[:.](\d)$/', $roh, $t) ? "$t[1]:$t[2],$t[3]" : $roh;
    }

    /** „Piccolo Knaben 16-17", „Volksläufer 08+älter", „Eltern/Kind 20" → ohne Jahrgänge */
    private static function ohneAlter(string $name): string
    {
        return trim((string) preg_replace('/\s+(?:\d{2,4}\s*-\s*\d{2,4}|\d{2,4}\s*\+\s*älter|\+?\d{2,4}\+?)$/u', '', $name));
    }
}
