<?php
/**
 * Prüft die Jahresautomatik (user/themes/abendlauf/classes/Saison.php).
 *     php werkzeuge/pruefe-saison.php
 */
require __DIR__ . '/../user/themes/abendlauf/classes/Saison.php';
use Grav\Theme\Abendlauf\Saison;

date_default_timezone_set('Europe/Zurich');
$abende = ['2027-08-18 17:25', '2027-08-25 17:25', '2027-09-01 17:25'];
$t = fn(string $s) => strtotime($s);
$fehler = 0;

function soll(string $was, $ist, $soll) {
    global $fehler;
    $ok = $ist === $soll;
    if (!$ok) $fehler++;
    printf("  %s  %-58s %s\n", $ok ? 'ok ' : 'FEHLER', $was, $ok ? '' : 'ist ' . json_encode($ist) . ', soll ' . json_encode($soll));
}
$kurz = fn($r) => $r === null ? null : $r['jahr'] . '/' . $r['lauf'];

echo "Dateiname\n";
soll('2027-2-abend.pdf',               $kurz(Saison::rangliste('2027-2-abend.pdf', $abende, $t('2026-01-01'))), '2027/2');
soll('Rangliste Lauf 3 2027.pdf',      $kurz(Saison::rangliste('Rangliste Lauf 3 2027.pdf', $abende, $t('2026-01-01'))), '2027/3');
soll('Rangliste_Lauf_Nr_1_2027.pdf',   $kurz(Saison::rangliste('Rangliste_Lauf_Nr_1_2027.pdf', $abende, $t('2026-01-01'))), '2027/1');
soll('Gesamtrangliste 2027.pdf',       $kurz(Saison::rangliste('Gesamtrangliste 2027.pdf', $abende, $t('2026-01-01'))), '2027/gesamt');
soll('2026-gesamt.pdf',                $kurz(Saison::rangliste('2026-gesamt.pdf', $abende, $t('2026-01-01'))), '2026/gesamt');

echo "Kalender\n";
soll('rangliste.pdf am 18.08. um 20:30',  $kurz(Saison::rangliste('rangliste.pdf', $abende, $t('2027-08-18 20:30'))), '2027/1');
soll('export.pdf am Tag danach',          $kurz(Saison::rangliste('export.pdf', $abende, $t('2027-08-19 09:00'))), '2027/1');
soll('export.pdf am 25.08.',              $kurz(Saison::rangliste('export.pdf', $abende, $t('2027-08-25 21:00'))), '2027/2');
soll('export.pdf am 01.09., erste Datei', $kurz(Saison::rangliste('export.pdf', $abende, $t('2027-09-01 21:00'))), '2027/3');
soll('export.pdf am 01.09., zweite Datei',$kurz(Saison::rangliste('export2.pdf', $abende, $t('2027-09-01 21:05'), [['jahr' => 2027, 'lauf' => '3']])), '2027/gesamt');
soll('gesamt.pdf am 01.09.',              $kurz(Saison::rangliste('gesamt.pdf', $abende, $t('2027-09-01 21:05'))), '2027/gesamt');
soll('Rangliste Lauf 2.pdf am 01.09. (Nachtrag)', $kurz(Saison::rangliste('Rangliste Lauf 2.pdf', $abende, $t('2027-09-01 21:00'))), '2027/2');

echo "Nicht raten\n";
soll('export.pdf im Juni',                $kurz(Saison::rangliste('export.pdf', $abende, $t('2027-06-10'))), null);
soll('export.pdf drei Wochen danach',     $kurz(Saison::rangliste('export.pdf', $abende, $t('2027-09-25'))), null);
soll('liste-2019.pdf während 2027',       $kurz(Saison::rangliste('liste-2019.pdf', $abende, $t('2027-08-18 21:00'))), null);

echo "Alben\n";
$alben = Saison::alben($abende);
soll('vier Alben',                count($alben), 4);
soll('Titel 1. Album',            $alben[0]['titel'], '1. Abend 2027');
soll('Datum 3. Album',            $alben[2]['datum'], '2027-09-01');
soll('Siegerehrung',              $alben[3]['slug'] . ' ' . $alben[3]['datum'], '2027-siegerehrung 2027-09-01');

echo "Platzhalter\n";
soll('Danketext', Saison::platzhalter('der {nummer}. Abendläufe {jahr} – {naechste_nummer}. im {naechstes_jahr}', 2027, 33), 'der 33. Abendläufe 2027 – 34. im 2028');
soll('Vorige Nummer', Saison::platzhalter('der {vorige_nummer}.', 2027, 33), 'der 32.');
soll('Termine',   Saison::platzhalter('am {termine}', 2027, 33, $abende), 'am 18.8. / 25.8. / 1.9.');

echo $fehler ? "\n$fehler Fehler\n" : "\nAlles in Ordnung.\n";
exit($fehler ? 1 : 0);
