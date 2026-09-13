<?php

namespace Grav\Theme;

use Grav\Common\Theme;
use Grav\Common\Yaml;
use Grav\Theme\Abendlauf\Rangliste;
use Grav\Theme\Abendlauf\Saison;
use RocketTheme\Toolbox\Event\Event;

require_once __DIR__ . '/classes/Saison.php';
require_once __DIR__ . '/classes/Rangliste.php';

/**
 * Theme der Urner Abendläufe.
 *
 * - Links auf fremde Seiten werden markiert, damit sie in einem neuen
 *   Tab öffnen und im Mitteilungsbereich den „Link"-Chip bekommen.
 * - Jahresautomatik (Regeln in classes/Saison.php):
 *   · eine hochgeladene Rangliste bekommt Jahr, Lauf und Titel von selbst
 *   · sobald die Laufabende auf der Startseite gespeichert werden, liegen
 *     die Fotoalben der Saison bereit – leer bleiben sie unsichtbar
 *   · Platzhalter wie {nummer} in Texten
 *   · Podest je Abend: die ersten drei jeder Kategorie aus den Ranglisten-PDFs
 */
class Abendlauf extends Theme
{
    public static function getSubscribedEvents(): array
    {
        return [
            'onTwigExtensions'      => ['onTwigExtensions', 0],
            // Admin2 speichert über das API-Plugin …
            'onApiMediaUploaded'    => ['onMediaHochgeladen', 0],
            'onApiPageUpdated'      => ['onSeiteGespeichert', 0],
            // … das klassische Admin über diese Ereignisse.
            'onAdminAfterAddMedia'  => ['onMediaHochgeladen', 0],
            'onAdminAfterSave'      => ['onSeiteGespeichert', 0],
        ];
    }

    /** Neue PDFs auf der Ranglisten-Seite zuordnen. */
    public function onMediaHochgeladen(Event $event): void
    {
        $page = $event['page'] ?? $event['object'] ?? null;
        if (!$page || !method_exists($page, 'template') || $page->template() !== 'ranglisten') {
            return;
        }
        $ordner = $page->path();
        if (!$ordner || !is_dir($ordner)) {
            return;
        }

        $abende = $this->laufabende();
        $vorhanden = [];
        foreach (glob($ordner . '/*.pdf.meta.yaml') ?: [] as $meta) {
            $vorhanden[] = (array) Yaml::parse((string) file_get_contents($meta));
        }

        foreach (glob($ordner . '/*.pdf') ?: [] as $pdf) {
            $meta = $pdf . '.meta.yaml';
            if (is_file($meta)) {
                continue;   // schon beschriftet – nie überschreiben
            }
            $treffer = Saison::rangliste(basename($pdf), $abende, time(), $vorhanden);
            if ($treffer === null) {
                continue;   // nicht erkannt – bleibt fürs Stift-Symbol
            }
            file_put_contents($meta, Yaml::dump([
                'titel' => $treffer['titel'],
                'art'   => $treffer['art'],
                'jahr'  => $treffer['jahr'],
                'lauf'  => $treffer['lauf'],
            ]));
            $vorhanden[] = $treffer;
            $this->podest($pdf);   // gleich auslesen, damit der erste Besuch nicht wartet
        }
    }

    /** Nach dem Speichern der Startseite die Alben der Saison bereitlegen. */
    public function onSeiteGespeichert(Event $event): void
    {
        $page = $event['page'] ?? $event['object'] ?? null;
        if (!$page || !method_exists($page, 'template') || $page->template() !== 'home') {
            return;
        }
        $this->albenBereitlegen();
    }

    /**
     * Legt fehlende Alben an. Vorhandene – auch umbenannte – bleiben
     * unberührt; erkannt wird ein Album an Jahr und Lauf.
     */
    public function albenBereitlegen(): int
    {
        $wurzel = $this->grav['locator']->findResource('page://', true);
        $fotos = $wurzel ? (glob($wurzel . '/*.fotos', GLOB_ONLYDIR) ?: [])[0] ?? null : null;
        if (!$fotos) {
            return 0;
        }

        $bestehend = [];
        $hoechste = 0;
        $fotograf = '';
        $juengstes = '';
        foreach (glob($fotos . '/*', GLOB_ONLYDIR) ?: [] as $dir) {
            if (preg_match('/^(\d+)\./', basename($dir), $m)) {
                $hoechste = max($hoechste, (int) $m[1]);
            }
            $md = $dir . '/album.md';
            if (is_file($md) && preg_match('/^---\s*\n(.*?)\n---/s', (string) file_get_contents($md), $k)) {
                $h = (array) Yaml::parse($k[1]);
                $bestehend[($h['jahr'] ?? '') . '/' . ($h['lauf'] ?? '')] = true;
                // Wer zuletzt fotografiert hat, macht es meist wieder.
                $datum = (string) ($h['datum'] ?? '');
                if (!empty($h['fotograf']) && $datum >= $juengstes) {
                    $juengstes = $datum;
                    $fotograf = (string) $h['fotograf'];
                }
            }
        }

        $neu = 0;
        foreach (Saison::alben($this->laufabende()) as $album) {
            if (isset($bestehend[$album['jahr'] . '/' . $album['lauf']])) {
                continue;
            }
            $hoechste++;
            $dir = sprintf('%s/%02d.%s', $fotos, $hoechste, $album['slug']);
            if (!is_dir($dir) && !mkdir($dir, 0775, true)) {
                continue;
            }
            file_put_contents($dir . '/album.md', "---\n" . Yaml::dump([
                'title'        => $album['titel'],
                'jahr'         => $album['jahr'],
                'lauf'         => $album['lauf'],
                'datum'        => $album['datum'],
                'beschreibung' => '',
                'fotograf'     => $fotograf,
                'cover'        => '',
            ]) . "---\n");
            $neu++;
        }
        return $neu;
    }

    /** @return string[] Laufabende der Startseite */
    private function laufabende(): array
    {
        $wurzel = $this->grav['locator']->findResource('page://', true);
        $datei = $wurzel ? (glob($wurzel . '/*/home.md') ?: [])[0] ?? null : null;
        if (!$datei || !preg_match('/^---\s*\n(.*?)\n---/s', (string) file_get_contents($datei), $k)) {
            return [];
        }
        $kopf = (array) Yaml::parse($k[1]);
        $abende = [];
        foreach ((array) ($kopf['laufabende'] ?? []) as $t) {
            if (!empty($t['datum'])) {
                $abende[] = is_int($t['datum']) ? date('Y-m-d H:i', $t['datum']) : (string) $t['datum'];
            }
        }
        return $abende;
    }

    /**
     * Podest einer Ranglisten-PDF (Regeln in classes/Rangliste.php). Das
     * Auslesen dauert rund 0,3 s; das Ergebnis liegt deshalb in
     * user/data/ranglisten/ und wird erst neu erzeugt, wenn sich die PDF oder
     * Rangliste::FORMAT ändert.
     *
     * @return array{datum: ?string, kategorien: list<array<string,mixed>>}
     */
    public function podest(string $pdf): array
    {
        $leer = ['datum' => null, 'kategorien' => []];
        if ($pdf === '' || !is_file($pdf)) {
            return $leer;
        }
        $ordner = $this->grav['locator']->findResource('user-data://', true) . '/ranglisten';
        $ablage = $ordner . '/' . pathinfo($pdf, PATHINFO_FILENAME) . '.json';
        $kennung = Rangliste::FORMAT . '-' . filesize($pdf) . '-' . filemtime($pdf);

        if (is_file($ablage)) {
            $gespeichert = json_decode((string) file_get_contents($ablage), true);
            if (is_array($gespeichert) && ($gespeichert['kennung'] ?? '') === $kennung) {
                return $gespeichert['daten'] ?? $leer;
            }
        }
        try {
            self::pdfBibliothek();
            $daten = Rangliste::lesen((new \Smalot\PdfParser\Parser())->parseFile($pdf)->getText());
        } catch (\Throwable $e) {
            // Unlesbar: Die Spalte zeigt dann nur den PDF-Link. Erst eine
            // neue Fassung der Datei wird wieder ausgelesen.
            $this->grav['log']->warning('Rangliste nicht lesbar: ' . basename($pdf) . ' – ' . $e->getMessage());
            $daten = $leer;
        }
        if (is_dir($ordner) || @mkdir($ordner, 0775, true)) {
            file_put_contents($ablage, json_encode(['kennung' => $kennung, 'daten' => $daten], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        }
        return $daten;
    }

    /** smalot/pdfparser liegt unter lib/pdfparser (siehe LIESMICH.md) */
    private static function pdfBibliothek(): void
    {
        if (class_exists(\Smalot\PdfParser\Parser::class)) {
            return;
        }
        spl_autoload_register(static function (string $klasse): void {
            $datei = __DIR__ . '/lib/pdfparser/src/' . str_replace('\\', '/', $klasse) . '.php';
            if (strncmp($klasse, 'Smalot\\PdfParser\\', 17) === 0 && is_file($datei)) {
                require $datei;
            }
        });
    }

    public function onTwigExtensions(): void
    {
        // {{ rangliste_podest(datei) }} – datei ist ein Medium der Ranglisten-Seite
        $this->grav['twig']->twig()->addFunction(
            new \Twig\TwigFunction('rangliste_podest', function ($datei): array {
                $pfad = is_object($datei) && method_exists($datei, 'get') ? (string) $datei->get('filepath') : (string) $datei;
                return $this->podest($pfad);
            })
        );

        // {{ text|platzhalter(jahr, nummer, abende, sponsoren) }}
        $this->grav['twig']->twig()->addFilter(
            new \Twig\TwigFilter('platzhalter', static function (?string $text, $jahr, $nummer, $abende = [], $sponsoren = []): string {
                return Saison::platzhalter((string) $text, (int) (string) $jahr, (int) (string) $nummer, array_map(
                    static fn ($a) => is_int($a) ? date('Y-m-d H:i', $a) : (string) $a,
                    (array) $abende
                ), array_map(static fn ($s) => (array) $s, (array) $sponsoren));
            })
        );

        $this->grav['twig']->twig()->addFilter(
            new \Twig\TwigFilter('externe_links', function (?string $html): string {
                if ($html === null || $html === '') {
                    return '';
                }
                $eigenerHost = parse_url($this->grav['uri']->rootUrl(true), PHP_URL_HOST);

                return preg_replace_callback(
                    '/<a\s+([^>]*?)href="(https?:\/\/[^"]+)"([^>]*)>/i',
                    static function (array $treffer) use ($eigenerHost): string {
                        $host = parse_url($treffer[2], PHP_URL_HOST);
                        if ($host === null || $host === $eigenerHost) {
                            return $treffer[0];
                        }
                        return '<a ' . $treffer[1] . 'href="' . $treffer[2] . '"' . $treffer[3]
                             . ' target="_blank" rel="noopener" data-extern>';
                    },
                    $html
                );
            }, ['is_safe' => ['html']])
        );
    }
}
