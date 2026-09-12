<?php

/**
 * Eigene Erweiterungen für die Urner Abendläufe.
 */
Kirby::plugin('abendlauf/core', [
    'fieldMethods' => [
        /**
         * Wie kirbytext(), markiert aber Links auf fremde Seiten:
         * sie öffnen in einem neuen Tab und bekommen ein Merkmal,
         * an dem das CSS den kleinen „Link"-Chip aufhängt.
         */
        'kirbytextExtern' => function ($field) {
            $html = $field->kirbytext()->value();
            $eigenerHost = parse_url(site()->url(), PHP_URL_HOST);

            return preg_replace_callback(
                '/<a\s+([^>]*?)href="(https?:\/\/[^"]+)"([^>]*)>/i',
                function ($treffer) use ($eigenerHost) {
                    $host = parse_url($treffer[2], PHP_URL_HOST);
                    if ($host === null || $host === $eigenerHost) {
                        return $treffer[0];
                    }
                    return '<a ' . $treffer[1] . 'href="' . $treffer[2] . '"' . $treffer[3]
                         . ' target="_blank" rel="noopener" data-extern>';
                },
                $html
            );
        },
    ],
]);
