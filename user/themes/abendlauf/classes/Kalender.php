<?php

namespace Grav\Theme\Abendlauf;

/**
 * Laufabende als iCalendar (RFC 5545), damit sie sich in Kalender-Apps
 * abonnieren lassen: /kalender.ics. Neue Daten und Absagen kommen beim
 * Abonnenten von selbst an. Ohne Grav-Abhängigkeit, geprüft von
 * werkzeuge/pruefe-kalender.php.
 */
class Kalender
{
    /** Übliches Ende eines Laufabends – am letzten Abend ist um 20 Uhr Rangverkündigung. */
    public const ENDE = '20:30';

    /**
     * @param list<array{start: int|string, titel: string, ort?: string, beschreibung?: string|list<string>, status?: string, url?: string}> $termine
     */
    public static function ics(array $termine, string $name = 'Urner Abendläufe', ?int $jetzt = null): string
    {
        $jetzt ??= time();
        $zeilen = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//STV Altdorf//Urner Abendlaeufe//DE',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            'X-WR-CALNAME:' . self::text($name),
            'X-WR-TIMEZONE:Europe/Zurich',
            'REFRESH-INTERVAL;VALUE=DURATION:PT12H',
            'X-PUBLISHED-TTL:PT12H',
            // Zeitzone ausgeschrieben, damit auch ältere Kalender die Sommerzeit kennen
            'BEGIN:VTIMEZONE',
            'TZID:Europe/Zurich',
            'BEGIN:DAYLIGHT',
            'TZOFFSETFROM:+0100',
            'TZOFFSETTO:+0200',
            'TZNAME:CEST',
            'DTSTART:19700329T020000',
            'RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=-1SU',
            'END:DAYLIGHT',
            'BEGIN:STANDARD',
            'TZOFFSETFROM:+0200',
            'TZOFFSETTO:+0100',
            'TZNAME:CET',
            'DTSTART:19701025T030000',
            'RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=-1SU',
            'END:STANDARD',
            'END:VTIMEZONE',
        ];

        $zone = new \DateTimeZone('Europe/Zurich');
        [$stunde, $minute] = array_map('intval', explode(':', self::ENDE));
        foreach ($termine as $t) {
            $start = (new \DateTimeImmutable('@' . (int) $t['start']))->setTimezone($zone);
            $ende = $start->setTime($stunde, $minute);
            if ($ende <= $start) {
                $ende = $start->modify('+2 hours');
            }
            $status = (string) ($t['status'] ?? 'findet-statt');
            $vorsatz = ['abgesagt' => 'Abgesagt: ', 'verschoben' => 'Verschoben: '][$status] ?? '';
            $beschreibung = $t['beschreibung'] ?? '';
            if (is_array($beschreibung)) {
                $beschreibung = implode("\n\n", array_filter(array_map('trim', $beschreibung)));
            }

            $zeilen[] = 'BEGIN:VEVENT';
            $zeilen[] = 'UID:laufabend-' . $start->format('Ymd') . '@abendlauf.ch';
            $zeilen[] = 'DTSTAMP:' . gmdate('Ymd\THis\Z', $jetzt);
            $zeilen[] = 'DTSTART;TZID=Europe/Zurich:' . $start->format('Ymd\THis');
            $zeilen[] = 'DTEND;TZID=Europe/Zurich:' . $ende->format('Ymd\THis');
            $zeilen[] = 'SUMMARY:' . self::text($vorsatz . $t['titel']);
            if (!empty($t['ort'])) {
                $zeilen[] = 'LOCATION:' . self::text((string) $t['ort']);
            }
            if ($beschreibung !== '') {
                $zeilen[] = 'DESCRIPTION:' . self::text((string) $beschreibung);
            }
            if (!empty($t['url'])) {
                $zeilen[] = 'URL:' . $t['url'];
            }
            $zeilen[] = 'STATUS:' . (['abgesagt' => 'CANCELLED', 'verschoben' => 'TENTATIVE'][$status] ?? 'CONFIRMED');
            $zeilen[] = 'END:VEVENT';
        }
        $zeilen[] = 'END:VCALENDAR';

        return implode("\r\n", array_map([self::class, 'falten'], $zeilen)) . "\r\n";
    }

    /** Backslash, Semikolon, Komma und Zeilenumbrüche maskieren */
    private static function text(string $s): string
    {
        return str_replace(['\\', ';', ',', "\r\n", "\n"], ['\\\\', '\;', '\,', '\n', '\n'], $s);
    }

    /** Zeilen über 75 Bytes umbrechen, ohne ein UTF-8-Zeichen zu zerschneiden */
    private static function falten(string $zeile): string
    {
        $aus = '';
        $max = 75;
        while (strlen($zeile) > $max) {
            $n = $max;
            while ($n > 0 && (ord($zeile[$n]) & 0xC0) === 0x80) {
                $n--;
            }
            $aus .= substr($zeile, 0, $n) . "\r\n ";
            $zeile = substr($zeile, $n);
            $max = 74;   // Folgezeilen beginnen mit einem Leerzeichen
        }
        return $aus . $zeile;
    }
}
