<?php

// Ohne das laufen Datumsangaben in UTC — der Countdown wäre zwei
// Stunden daneben und Termine könnten aufs falsche Datum rutschen.
date_default_timezone_set('Europe/Zurich');

return [
    // Panel auf Deutsch, Schweizer Datums- und Zahlenformate.
    'panel' => [
        'language' => 'de',
        'install'  => true,
    ],
    // Bewusst NICHT 'intl': dort folgt das Format den ICU-Zeichen, wo
    // "m" die Minute meint und "d.m.Y" zu "3.0.2026" wird. Mit dem
    // Standardhandler gilt die gewohnte PHP-Schreibweise.
    'date' => [
        'handler' => 'date',
    ],
    'locale' => 'de_CH.UTF-8',

    // Bildgrössen werden beim ersten Aufruf erzeugt und danach zwischengespeichert.
    'thumbs' => [
        'srcsets' => [
            'default' => [400, 800, 1200, 1600],
        ],
        'quality' => 82,
        'format'  => 'webp',
    ],

    // Fotoalben können sehr gross werden - Cache spart Rechenzeit beim Hoster.
    'cache' => [
        'pages' => [
            'active' => false, // fuer die Entwicklung aus; vor dem Livegang an
        ],
    ],

    'smartypants' => true,
];
