<?php

namespace App\Console\Commands;

use App\Models\TournamentType;
use App\Services\TournamentScoringService;
use Illuminate\Console\Command;

class SyncTournamentScoringAssignments extends Command
{
    protected $signature = 'tournaments:sync-scoring {--type= : ID del tipo de torneo}';

    protected $description = 'Sincroniza las puntuaciones asignadas con las tablas vigentes de los tipos de torneo';

    public function handle(TournamentScoringService $service): int
    {
        $query = TournamentType::query()
            ->where('assigns_points', true)
            ->orderBy('id');

        if ($this->option('type')) {
            $query->whereKey((int) $this->option('type'));
        }

        $types = $query->get();

        if ($types->isEmpty()) {
            $this->warn('No se encontraron tipos de torneo para sincronizar.');

            return self::SUCCESS;
        }

        foreach ($types as $type) {
            $service->synchronizeTypeAssignments($type);
            $this->line("Sincronizado: {$type->name}");
        }

        $this->info('Las puntuaciones quedaron sincronizadas correctamente.');

        return self::SUCCESS;
    }
}
