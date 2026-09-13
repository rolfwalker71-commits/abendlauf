<?php
/**
 * Prüft den abonnierbaren Kalender (user/themes/abendlauf/classes/Kalender.php).
 *     php werkzeuge/pruefe-kalender.php
 */
require __DIR__ . '/../user/themes/abendlauf/classes/Kalender.php';
use Grav\Theme\Abendlauf\Kalender;

date_default_timezone_set('Europe/Zurich');
$fehler = 0;

function soll(string $was, $ist, $soll) {
    global $fehler;
    $ok = $ist === $soll;
    if (!$ok) $fehler++;
    printf("  %s  %-58s %s\n", $ok ? 'ok ' : 'FEHLER', $was, $ok ? '' : 'ist ' . json_encode($ist, JSON_UNESCAPED_UNICODE) . ', soll ' . json_encode($soll, JSON_UNESCAPED_UNICODE));
}

$lang = str_repeat('Mit Rangverkündigung um 20 Uhr, danach Apéro; ', 4);
$ics = Kalender::ics([
    ['start' => strtotime('2027-08-18 17:25'), 'titel' => '33. Urner Abendläufe · Erster Abend', 'ort' => 'Seerestaurant Seedorf',
     'beschreibung' => ['', 'Startzeiten: https://www.abendlauf.ch/ausschreibung'], 'url' => 'https://www.abendlauf.ch/'],
    ['start' => strtotime('2027-09-01 17:25'), 'titel' => 'Dritter Abend', 'status' => 'abgesagt', 'beschreibung' => $lang],
    ['start' => strtotime('2027-12-10 21:00'), 'titel' => 'Spät', 'status' => 'verschoben'],
], 'Urner Abendläufe', strtotime('2027-01-01 12:00'));

$zeilen = explode("\r\n", rtrim($ics, "\r\n"));
$entfaltet = str_replace("\r\n ", '', $ics);

echo "Aufbau\n";
soll('beginnt und endet als Kalender',        [$zeilen[0], end($zeilen)], ['BEGIN:VCALENDAR', 'END:VCALENDAR']);
soll('nur CRLF als Zeilenende',                preg_match("/(?<!\r)\n/", $ics), 0);
soll('keine Zeile länger als 75 Bytes',        max(array_map('strlen', $zeilen)) <= 75, true);
soll('jede Zeile gültiges UTF-8',              count(array_filter($zeilen, fn ($z) => !mb_check_encoding($z, 'UTF-8'))), 0);
soll('drei Termine',                           substr_count($ics, 'BEGIN:VEVENT'), 3);

echo "Termin\n";
soll('Start in Schweizer Zeit',                str_contains($ics, 'DTSTART;TZID=Europe/Zurich:20270818T172500'), true);
soll('Ende um 20:30',                          str_contains($ics, 'DTEND;TZID=Europe/Zurich:20270818T203000'), true);
soll('Ende nach 20:30 → zwei Stunden',         str_contains($ics, 'DTEND;TZID=Europe/Zurich:20271210T230000'), true);
soll('stabile UID je Tag',                     str_contains($ics, 'UID:laufabend-20270818@abendlauf.ch'), true);
soll('Zeitstempel in UTC',                     str_contains($ics, 'DTSTAMP:20270101T110000Z'), true);
soll('Titel mit Punkt',                        str_contains($entfaltet, 'SUMMARY:33. Urner Abendläufe · Erster Abend'), true);
soll('leere Beschreibungsteile fallen weg',    str_contains($entfaltet, 'DESCRIPTION:Startzeiten: https://www.abendlauf.ch/ausschreibung'), true);

echo "Status und Sonderzeichen\n";
soll('Absage im Titel und als CANCELLED',      str_contains($ics, 'SUMMARY:Abgesagt: Dritter Abend') && str_contains($ics, 'STATUS:CANCELLED'), true);
soll('verschoben als TENTATIVE',               str_contains($ics, 'STATUS:TENTATIVE'), true);
soll('normal als CONFIRMED',                   substr_count($ics, 'STATUS:CONFIRMED'), 1);
soll('Komma und Semikolon maskiert',           str_contains($entfaltet, 'um 20 Uhr\, danach Apéro\; Mit'), true);
soll('lange Beschreibung verlustfrei gefaltet', str_contains($entfaltet, 'DESCRIPTION:' . str_replace([',', ';'], ['\,', '\;'], $lang)), true);

echo $fehler ? "\n$fehler Fehler\n" : "\nAlles in Ordnung.\n";
exit($fehler ? 1 : 0);
