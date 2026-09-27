<?php

namespace Grav\Theme\Abendlauf;

/**
 * Höhenprofil einer Runde aus einer GPX-Datei (Streckenseite).
 *
 * Ohne Grav-Abhängigkeit, getestet von werkzeuge/pruefe-hoehenprofil.php.
 * Die GPX-Datei braucht Höhenangaben (<ele>) je Punkt; die Distanz wird
 * aus den Koordinaten berechnet.
 */
class Hoehenprofil
{
    /**
     * Zeichenfläche des SVG (viewBox). Das SVG wird ohne Seitenverhältnis auf
     * die Spaltenbreite gezogen; die Beschriftung steht als HTML daneben,
     * damit sie auf dem Handy nicht mitschrumpft.
     */
    public const BREITE = 600;
    public const HOEHE = 150;

    /**
     * Mindestspanne der Höhenachse in Metern. Ohne sie würde ein Unterschied
     * von drei Metern die ganze Höhe füllen und wie ein Hügel aussehen.
     */
    public const SPANNE = 20;

    /**
     * Punkte [Distanz in m, Höhe in m] aus dem GPX-Text.
     *
     * @return list<array{0: float, 1: float}>
     */
    public static function punkte(string $gpx): array
    {
        if (!preg_match_all('/<(?:trkpt|rtept)\b([^>]*)>(.*?)<\/(?:trkpt|rtept)>/s', $gpx, $treffer, PREG_SET_ORDER)) {
            return [];
        }
        $punkte = [];
        $vorher = null;
        $distanz = 0.0;
        foreach ($treffer as [, $attribute, $inhalt]) {
            if (!preg_match('/\blat="([-\d.]+)"/', $attribute, $lat)
                || !preg_match('/\blon="([-\d.]+)"/', $attribute, $lon)
                || !preg_match('/<ele>\s*([-\d.]+)\s*<\/ele>/', $inhalt, $ele)) {
                continue;
            }
            $jetzt = [(float) $lat[1], (float) $lon[1]];
            if ($vorher !== null) {
                $distanz += self::abstand($vorher, $jetzt);
            }
            $punkte[] = [$distanz, (float) $ele[1]];
            $vorher = $jetzt;
        }
        return $punkte;
    }

    /** Abstand zweier Punkte [lat, lon] in Metern (Haversine) */
    public static function abstand(array $a, array $b): float
    {
        $r = 6371000.0;
        $dLat = deg2rad($b[0] - $a[0]);
        $dLon = deg2rad($b[1] - $a[1]);
        $h = sin($dLat / 2) ** 2 + cos(deg2rad($a[0])) * cos(deg2rad($b[0])) * sin($dLon / 2) ** 2;
        return 2 * $r * asin(min(1.0, sqrt($h)));
    }

    /**
     * Alles, was die Vorlage zum Zeichnen braucht.
     *
     * $laenge: offizielle Distanz der Runde in m (Feld „Distanz“). Die aus der
     * GPX gemessene Länge weicht davon um einige Prozent ab; die Distanzachse
     * wird darauf gestreckt, damit Profil und Angabe zusammenpassen.
     */
    public static function ansicht(array $punkte, ?float $laenge = null): ?array
    {
        if (count($punkte) < 2) {
            return null;
        }
        $gemessen = $punkte[count($punkte) - 1][0];
        if ($gemessen <= 0) {
            return null;
        }
        $faktor = ($laenge !== null && $laenge > 0) ? $laenge / $gemessen : 1.0;
        $laenge = $gemessen * $faktor;

        $hoehen = array_column($punkte, 1);
        $tief = min($hoehen);
        $hoch = max($hoehen);

        // Höhenachse: mindestens SPANNE Meter, auf 5 m gerundet, um das Profil zentriert
        $spanne = max(self::SPANNE, (int) (ceil(($hoch - $tief + 4) / 5) * 5));
        $unten = round((($tief + $hoch) / 2 - $spanne / 2) / 5) * 5;
        if ($unten + $spanne < $hoch + 1) {
            $unten += 5;
        } elseif ($unten > $tief - 1) {
            $unten -= 5;
        }
        $oben = $unten + $spanne;

        $w = self::BREITE;
        $hFlaeche = self::HOEHE;
        $x = static fn (float $d): float => round($d * $faktor / $laenge * $w, 1);
        $y = static fn (float $h): float => round(($oben - $h) / ($oben - $unten) * $hFlaeche, 1);

        $linie = [];
        $daten = [];
        foreach ($punkte as [$d, $h]) {
            $linie[] = $x($d) . ',' . $y($h);
            $daten[] = round($d * $faktor) . ':' . round($h, 1);
        }
        $pfad = 'M' . implode(' L', $linie);
        $flaeche = $pfad . ' L' . $w . ',' . $hFlaeche . ' L0,' . $hFlaeche . ' Z';

        // Rasterlinien alle 5 m; Lage in Prozent der Zeichenfläche (für das HTML)
        $hTicks = [];
        for ($h = $unten; $h <= $oben + 0.01; $h += 5) {
            $hTicks[] = ['wert' => (int) $h, 'y' => $y($h), 'prozent' => round(($oben - $h) / ($oben - $unten) * 100, 2)];
        }
        $schritt = 100;
        foreach ([100, 200, 250, 500, 1000] as $s) {
            $schritt = $s;
            if ($laenge / $s <= 6) {
                break;
            }
        }
        $dTicks = [];
        for ($d = 0; $d <= $laenge + 0.5; $d += $schritt) {
            $dTicks[] = ['wert' => (int) $d, 'prozent' => round($d / $laenge * 100, 2)];
        }

        return [
            'breite'  => $w,
            'hoehe'   => $hFlaeche,
            'linie'   => $pfad,
            'flaeche' => $flaeche,
            'hticks'  => $hTicks,
            'dticks'  => $dTicks,
            'tief'    => (int) round($tief),
            'hoch'    => (int) round($hoch),
            'laenge'  => (int) round($laenge),
            // „Distanz:Höhe;…“ für die Anzeige beim Überfahren (js/site.js)
            'daten'   => implode(';', $daten),
            'hmin'    => $unten,
            'hmax'    => $oben,
        ];
    }
}
