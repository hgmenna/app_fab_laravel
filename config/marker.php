<?php

return [
    'url' => env('MARKER_SERVER_URL'),
    'production_url' => 'https://marcador.federacionargentinadebillar.org',
    'local_url' => 'http://127.0.0.1:8792',
    'pilot_key' => env('MARKER_PILOT_KEY'),
    'pilot_tournament_id' => env('MARKER_PILOT_TOURNAMENT_ID'),
    'pilot_clubs' => env('MARKER_PILOT_CLUBS', 'club-demo,club-sur'),
];
