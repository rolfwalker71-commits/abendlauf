<?php
/**
 * Prüft das Auslesen der Ranglisten (user/themes/abendlauf/classes/Rangliste.php).
 *     php werkzeuge/pruefe-rangliste.php
 * Liegen die PDFs des neuesten Jahrgangs lokal vor, werden sie zusätzlich
 * mit der mitgelieferten Bibliothek ausgelesen und zusammengefasst.
 */
require __DIR__ . '/../user/themes/abendlauf/classes/Rangliste.php';
use Grav\Theme\Abendlauf\Rangliste;

$fehler = 0;

function soll(string $was, $ist, $soll) {
    global $fehler;
    $ok = $ist === $soll;
    if (!$ok) $fehler++;
    printf("  %s  %-58s %s\n", $ok ? 'ok ' : 'FEHLER', $was, $ok ? '' : 'ist ' . json_encode($ist, JSON_UNESCAPED_UNICODE) . ', soll ' . json_encode($soll, JSON_UNESCAPED_UNICODE));
}

// Aufbau wie in den echten PDFs, Namen erfunden
$abend = <<<TXT
33. Urner Abendläufe 2027
Rangliste pro Lauf Lauf Nr: 2 Datum: 25.08.2027
1210 m A Piccolo Knaben 17-18
Rang Nummer Nachname Vorname JahrgangOrt Zeit DifferenzZeit/Km Km/h
1 612 Muster Max 2017 Altdorf UR 04:35:7 - 03:48:9 15.8
2 650 Beispiel Jan Luca 2018 Isenthal 04:44:7 +00:09:0 03:55:3 15.3
3 799 Probe Elia 2017 Ried (Muotathal) 04:52:6 +00:16:9 04:02:8 14.9
3 800 Gleich Tim 2017 Bürglen UR 04:52:6 +00:16:9 04:02:8 14.9
5 703 Viert Nik 2017 Isenthal 04:53:2 +00:17:5 04:02:3 14.9
Mittwoch, 25. August 2027 21:18:49
33. Urner Abendläufe 2027
Rangliste pro Lauf Lauf Nr: 2 Datum: 25.08.2027
1210 m A Piccolo Knaben 17-18
Rang Nummer Nachname Vorname JahrgangOrt Zeit DifferenzZeit/Km Km/h
6 594 Sechst Arlik 2018 Schattdorf 06:03:4 +01:27:7 05:00:3 12.0
6700 m J Volksläufer 09+älter
Rang Nummer Nachname Vorname JahrgangOrt Zeit DifferenzZeit/Km Km/h
127 Ausser Konkurrenz 2007 Altdorf UR 30:35:0 +08:28:3 04:34:9 13.1
1 101 Schnell Lukas 1990 Altdorf UR 21:58:4 - 03:16:8 18.3
1210 m D Eltern/Kind 21
TXT;

echo "1. Abend\n";
$r = Rangliste::lesen($abend);
$k = $r['kategorien'];
soll('Datum aus dem Kopf',              $r['datum'], '2027-08-25');
soll('drei Kategorien',                 count($k), 3);
soll('Kategoriename ohne Jahrgänge',    $k[0]['name'], 'Piccolo Knaben');
soll('Kürzel und Distanz',              $k[0]['kuerzel'] . ' ' . $k[0]['distanz'], 'A 1210');
soll('Platz 3 doppelt vergeben → 4 Einträge', count($k[0]['plaetze']), 4);
soll('Sieger mit Ort und Zeit',         $k[0]['plaetze'][0], ['rang' => 1, 'name' => 'Muster Max', 'ort' => 'Altdorf UR', 'zeit' => '4:35,7']);
soll('Doppelter Vorname',               $k[0]['plaetze'][1]['name'], 'Beispiel Jan Luca');
soll('Ort mit Klammern',                [$k[0]['plaetze'][2]['ort'], $k[0]['plaetze'][2]['zeit']], ['Ried (Muotathal)', '4:52,6']);
soll('„08+älter" abgeschnitten',        $k[1]['name'], 'Volksläufer');
soll('Ohne Rang zählt nicht',           $k[1]['plaetze'], [['rang' => 1, 'name' => 'Schnell Lukas', 'ort' => 'Altdorf UR', 'zeit' => '21:58,4']]);
soll('Kategorie ohne Einträge',         [$k[2]['name'], $k[2]['plaetze']], ['Eltern/Kind', []]);

$gesamt = <<<TXT
Gesamtrangliste Datum: 01.09.2027
2260 m E Schülerinnen 14-16
Rang Nummer Nachname Vorname JahrgangOrt Lauf 1 Lauf 2 Lauf 3 Gesamt
1 58 Muster Anna 2014 Uster 08:29:3 08:27:7 16:57:0
2 612 Beispiel Lea 2015 Altdorf UR 09:11:7 08:43:3 09:01:0 17:55:0
3 77 Ohne Ort 2014 09:00:0 09:00:0 18:00:0
TXT;

echo "Gesamt\n";
$g = Rangliste::lesen($gesamt);
soll('Datum',                           $g['datum'], '2027-09-01');
soll('Summe ist die letzte Zeit (2 Läufe)', $g['kategorien'][0]['plaetze'][0]['zeit'], '16:57,0');
soll('Summe ist die letzte Zeit (3 Läufe)', $g['kategorien'][0]['plaetze'][1]['zeit'], '17:55,0');
soll('Zeile ohne Ort',                  [$g['kategorien'][0]['plaetze'][2]['ort'], $g['kategorien'][0]['plaetze'][2]['zeit']], ['', '18:00,0']);

echo "Zeitformat\n";
soll('führende Null',                   Rangliste::zeit('00:59:1'), '0:59,1');
soll('Punkt als Trenner',               Rangliste::zeit('24:31.5'), '24:31,5');

echo "Sekunden (Vergleich mit Rekorden)\n";
soll('Minuten',                         Rangliste::sekunden('4:09,2'), 249.2);
soll('mit Stunde',                      Rangliste::sekunden('1:02:03,4'), 3723.4);
soll('Punkt statt Komma',               Rangliste::sekunden('20:19.8'), 1219.8);
soll('gleiche Zeit ist gleich schnell', Rangliste::sekunden('4:09,2') <= Rangliste::sekunden('4:09,2'), true);
soll('keine Zeit',                      Rangliste::sekunden('DNF'), null);

// Echte PDFs des neuesten Jahrgangs, falls lokal vorhanden
$ordner = __DIR__ . '/../user/pages/04.ranglisten';
$pdfs = glob($ordner . '/[0-9][0-9][0-9][0-9]-*.pdf') ?: [];
if ($pdfs) {
    $jahr = max(array_map(fn ($p) => (int) substr(basename($p), 0, 4), $pdfs));
    spl_autoload_register(static function (string $klasse): void {
        $datei = __DIR__ . '/../user/themes/abendlauf/lib/pdfparser/src/' . str_replace('\\', '/', $klasse) . '.php';
        if (strncmp($klasse, 'Smalot\\PdfParser\\', 17) === 0 && is_file($datei)) require $datei;
    });
    echo "PDFs $jahr\n";
    foreach (glob("$ordner/$jahr-*.pdf") as $pdf) {
        $d = Rangliste::lesen((new \Smalot\PdfParser\Parser())->parseFile($pdf)->getText());
        $voll = count(array_filter($d['kategorien'], fn ($k) => count($k['plaetze']) >= 3));
        soll(sprintf('%-18s %d Kategorien, %d mit Podest, %s', basename($pdf), count($d['kategorien']), $voll, $d['datum']),
             $d['kategorien'] !== [] && $voll === count($d['kategorien']), true);
    }
}

echo $fehler ? "\n$fehler Fehler\n" : "\nAlles in Ordnung.\n";
exit($fehler ? 1 : 0);
