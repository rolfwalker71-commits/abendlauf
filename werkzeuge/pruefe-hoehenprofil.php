<?php
/**
 * Prüft das Höhenprofil der Streckenseite (user/themes/abendlauf/classes/Hoehenprofil.php),
 * mit den echten GPX-Dateien, falls vorhanden.
 *     php werkzeuge/pruefe-hoehenprofil.php
 */
require __DIR__ . '/../user/themes/abendlauf/classes/Hoehenprofil.php';
use Grav\Theme\Abendlauf\Hoehenprofil;

$fehler = 0;

function soll(string $was, $ist, $soll) {
    global $fehler;
    $ok = $ist === $soll;
    if (!$ok) $fehler++;
    printf("  %s  %-58s %s\n", $ok ? 'ok ' : 'FEHLER', $was, $ok ? '' : 'ist ' . json_encode($ist, JSON_UNESCAPED_UNICODE) . ', soll ' . json_encode($soll, JSON_UNESCAPED_UNICODE));
}

echo "GPX lesen\n";
$gpx = '<gpx><trk><trkseg>'
    . '<trkpt lat="46.88" lon="8.60"><ele>435.0</ele></trkpt>'
    . '<trkpt lat="46.881" lon="8.60"><ele>437.5</ele></trkpt>'
    . '<trkpt lat="46.881" lon="8.601"></trkpt>'                     // ohne Höhe: übersprungen
    . '<trkpt lon="8.602" lat="46.882"><ele> 434.2 </ele></trkpt>'   // Reihenfolge der Attribute egal
    . '</trkseg></trk></gpx>';
$p = Hoehenprofil::punkte($gpx);
soll('drei Punkte mit Höhe',                       count($p), 3);
soll('beginnt bei 0 m',                            $p[0][0], 0.0);
soll('0,001° Breite ≈ 111 m',                      (int) round($p[1][0]), 111);
soll('Höhe gelesen',                               $p[2][1], 434.2);
soll('leere Datei → keine Punkte',                 Hoehenprofil::punkte('<gpx></gpx>'), []);
soll('ein Punkt reicht nicht',                     Hoehenprofil::ansicht([[0.0, 435.0]]), null);

echo "Ansicht\n";
$a = Hoehenprofil::ansicht([[0.0, 434.0], [500.0, 438.0], [1000.0, 435.0]], 1210.0);
soll('Distanz auf offizielle Länge gestreckt',     $a['laenge'], 1210);
soll('letzter Punkt in den Daten bei 1210 m',      substr($a['daten'], -8), '1210:435');
soll('Höhenachse mindestens 20 m',                 $a['hmax'] - $a['hmin'] >= Hoehenprofil::SPANNE, true);
soll('Profil liegt ganz in der Achse',             $a['hmin'] <= 433.0 && $a['hmax'] >= 439.0, true);
soll('Achse auf 5 m gerundet',                     fmod($a['hmin'], 5) == 0.0, true);
soll('tiefster und höchster Punkt',                [$a['tief'], $a['hoch']], [434, 438]);
soll('Linie beginnt am linken Rand',               str_starts_with($a['linie'], 'M0,'), true);
soll('Linie endet am rechten Rand',                str_contains($a['linie'], ' L' . Hoehenprofil::BREITE . ','), true);
soll('Distanzmarken höchstens sechs',              count($a['dticks']) <= 7, true);
soll('letzte Distanzmarke nicht über die Länge',   end($a['dticks'])['wert'] <= 1210, true);

$steil = Hoehenprofil::ansicht([[0.0, 430.0], [1000.0, 470.0]]);
soll('grosser Unterschied: Achse wächst mit',      $steil['hmin'] <= 430.0 && $steil['hmax'] >= 470.0, true);

$dateien = glob(__DIR__ . '/../user/pages/03.strecke/*.gpx') ?: [];
if ($dateien) {
    echo "Echte GPX-Dateien\n";
    foreach ($dateien as $datei) {
        $p = Hoehenprofil::punkte((string) file_get_contents($datei));
        $a = Hoehenprofil::ansicht($p);
        printf("  %-18s %4d Punkte, %5d m, %d–%d m ü. M.\n", basename($datei), count($p), $a['laenge'], $a['tief'], $a['hoch']);
        preg_match_all('/<trkpt lat="([-\d.]+)" lon="([-\d.]+)"/', (string) file_get_contents($datei), $m, PREG_SET_ORDER);
        $start = [(float) $m[0][1], (float) $m[0][2]];
        $ziel = [(float) end($m)[1], (float) end($m)[2]];
        soll(basename($datei) . ': Start = Ziel (Rundkurs, < 30 m)', Hoehenprofil::abstand($start, $ziel) < 30, true);
    }
}

echo $fehler ? "\n$fehler Fehler\n" : "\nAlles in Ordnung\n";
exit($fehler ? 1 : 0);
