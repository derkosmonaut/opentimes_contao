<?php

declare(strict_types=1);

// Texte der Frontend-Ausgabe. Der Controller stellt sie den Templates als Variable "labels"
// bereit, z. B. {{ labels.status.open_until }}.
$GLOBALS['TL_LANG']['opentimes'] = [
    'status' => [
        'open' => 'Jetzt geöffnet',
        'open_until' => 'Jetzt geöffnet bis',
        'closed' => 'Zurzeit geschlossen',
        'opens_again' => 'Öffnet wieder am',
        'unknown' => 'Öffnungszeiten auf Anfrage',
    ],
    'closed' => 'Geschlossen',
    'time_suffix' => 'Uhr',
    'to' => 'bis',
    'week_caption' => 'Öffnungszeiten der nächsten Tage',
    'today' => 'heute',
    'today_hours' => 'Heute',
    'overnight' => 'bis zum Folgetag',
    'closure_note' => 'Hinweis zur Schließung',
    'col_day' => 'Tag',
    'col_hours' => 'Zeiten',
];
