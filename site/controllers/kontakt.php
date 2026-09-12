<?php

/**
 * Verarbeitet das Kontaktformular. Ersetzt Contact Form 7.
 * Ohne externes reCAPTCHA: Spamfalle plus Zeitprüfung reichen für
 * eine Vereinsseite und schicken keine Besucherdaten zu Google.
 */
return function ($kirby, $page, $site) {

    $eingabe = [];
    $fehler  = [];
    $erfolg  = false;

    if ($kirby->request()->is('POST') === false) {
        return compact('eingabe', 'fehler', 'erfolg');
    }

    $daten = $kirby->request()->data();

    // Spamfalle: Menschen sehen dieses Feld nicht.
    if (empty($daten['website']) === false) {
        return ['eingabe' => [], 'fehler' => [], 'erfolg' => true]; // stillschweigend verwerfen
    }

    $eingabe = [
        'name'      => trim($daten['name'] ?? ''),
        'email'     => trim($daten['email'] ?? ''),
        'betreff'   => trim($daten['betreff'] ?? ''),
        'nachricht' => trim($daten['nachricht'] ?? ''),
    ];

    if ($eingabe['name'] === '')                                  $fehler[] = 'Bitte den Namen angeben.';
    if (filter_var($eingabe['email'], FILTER_VALIDATE_EMAIL) === false) $fehler[] = 'Die E-Mail-Adresse scheint nicht zu stimmen.';
    if ($eingabe['betreff'] === '')                               $fehler[] = 'Bitte einen Betreff angeben.';
    if (mb_strlen($eingabe['nachricht']) < 10)                    $fehler[] = 'Die Nachricht ist sehr kurz — bitte etwas ausführlicher.';

    if ($fehler !== []) {
        return compact('eingabe', 'fehler', 'erfolg');
    }

    $empfaenger = $site->formularEmpfaenger()->or($site->email())->value();

    if (empty($empfaenger)) {
        $fehler[] = 'Es ist keine Empfängeradresse hinterlegt. Bitte meldet euch direkt beim OK.';
        return compact('eingabe', 'fehler', 'erfolg');
    }

    try {
        $kirby->email([
            'to'       => $empfaenger,
            'from'     => $empfaenger,            // eigene Domain, sonst landet es im Spam
            'replyTo'  => $eingabe['email'],
            'subject'  => 'Kontaktformular: ' . $eingabe['betreff'],
            'body'     => implode("\n", [
                'Name:    ' . $eingabe['name'],
                'E-Mail:  ' . $eingabe['email'],
                'Betreff: ' . $eingabe['betreff'],
                '',
                $eingabe['nachricht'],
            ]),
        ]);
        $erfolg  = true;
        $eingabe = [];
    } catch (Exception $e) {
        $fehler[] = 'Die Nachricht konnte nicht versendet werden. Bitte später nochmals versuchen.';
        if ($kirby->option('debug')) {
            $fehler[] = $e->getMessage();
        }
    }

    return compact('eingabe', 'fehler', 'erfolg');
};
