<?php

namespace App\Console\Commands;

use App\Models\TournamentType;
use App\Services\TournamentScoringService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SynchronizeTournamentScoring extends Command
{
    protected $signature = 'torneos:sincronizar-puntuacion {tipo? : ID o código del tipo de torneo}';

    protected $description = 'Aplica las tablas de puntuación vigentes a los torneos, con filtro opcional por tipo';

    public function handle(TournamentScoringService $scoring): int
    {
        $identifier = trim((string) $this->argument('tipo'));
        if ($identifier === '') {
            TournamentType::query()->where('assigns_points', true)->where('scoring_method', 'position')
                ->eachById(fn (TournamentType $type) => $this->synchronize($type, $scoring));

            return self::SUCCESS;
        }

        $types = TournamentType::query()
            ->when(ctype_digit($identifier), fn ($query) => $query->whereKey((int) $identifier),
                fn ($query) => $query->where('code', $identifier))
            ->get();
        if ($types->count() !== 1) {
            $this->error('Indicá el ID de un tipo de torneo o un código que identifique un solo tipo.');

            return self::FAILURE;
        }

        $this->synchronize($types->sole(), $scoring);

        return self::SUCCESS;
    }

    private function synchronize(TournamentType $type, TournamentScoringService $scoring): void
    {
        DB::transaction(fn () => $scoring->synchronizeTypeAssignments(
            TournamentType::query()->lockForUpdate()->findOrFail($type->id)
        ));

        $this->info('Puntuaciones sincronizadas para '.$type->name.'.');
    }
}
