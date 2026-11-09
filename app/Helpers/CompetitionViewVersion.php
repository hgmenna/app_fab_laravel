<?php

namespace App\Helpers;

final class CompetitionViewVersion
{
    /** La página y su consulta de resultados comparten la versión de estas vistas. */
    public static function current(bool $organization = false): string
    {
        $files = $organization ? [
            'filament/resources/tournaments/pages/manage-tournament-competition.blade.php',
            'filament/resources/tournaments/pages/partials/competition-bracket.blade.php',
            'filament/resources/tournaments/pages/partials/competition-qualified-action.blade.php',
            'filament/resources/tournaments/pages/partials/competition-section-tools.blade.php',
            'filament/resources/tournaments/pages/partials/marker-live-score.blade.php',
            'livewire/tournament-marker-live.blade.php',
        ] : [
            'public/five-quillas-competition.blade.php',
            'public/five-quillas-competition-broadcast.blade.php',
            'public/five-quillas-competition-unavailable.blade.php',
            'public/partials/competition-availability.blade.php',
            'partials/competition-finalists-script.blade.php',
        ];
        $files = array_merge($files, [
            'partials/competition-finalists-style.blade.php',
            'partials/competition-pdf-script.blade.php',
            'partials/competition-view-refresh.blade.php',
        ]);

        return substr(hash('sha256', implode('', array_map(
            fn (string $file): string => hash_file('sha256', resource_path('views/'.$file)),
            $files,
        ))), 0, 20);
    }
}
