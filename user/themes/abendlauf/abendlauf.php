<?php

namespace Grav\Theme;

use Grav\Common\Theme;

/**
 * Theme der Urner Abendläufe.
 *
 * Enthält nur eine Ergänzung: Links auf fremde Seiten werden markiert,
 * damit sie in einem neuen Tab öffnen und im Mitteilungsbereich den
 * kleinen „Link"-Chip bekommen. In Kirby war das ein Plugin.
 */
class Abendlauf extends Theme
{
    public static function getSubscribedEvents(): array
    {
        return [
            'onTwigExtensions' => ['onTwigExtensions', 0],
        ];
    }

    public function onTwigExtensions(): void
    {
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
