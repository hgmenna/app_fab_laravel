<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\GeneralRanking;
use App\Models\PlayerCategoryHistory;
use App\Models\RankingHistory;
use App\Models\Tournament;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SeedRankingCategoryBaseline extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
protected $signature = 'player-categories:seed-ranking-baseline
                        {season : Temporada a inicializar}
                        {--dry-run : Valida y muestra el baseline sin escribir en la base de datos}';
    /**
     * The console command description.
     *
     * @var string
     */
protected $description = 'Inicializa el estado base de categorías temporales M/N del Ranking General para una temporada';
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $season = (int) $this->argument('season');

        $this->info("Preparando baseline de categorías M/N para la temporada {$season}...");

        /*
        * 1) Validar que exista la Etapa 4 de ranking de la temporada.
        */
        $stage4 = Tournament::query()
            ->whereHas('type', fn ($query) =>
                $query->where('affects_ranking', true)
            )
            ->where('stage_number', 4)
            ->whereYear('end_date', $season)
            ->where('end_date', '<=', now())
            ->orderByDesc('end_date')
            ->first();

        if (!$stage4) {
            $this->error(
                "No existe una Etapa 4 de ranking finalizada para la temporada {$season}."
            );

            return self::FAILURE;
        }

        $this->info(
            "Etapa 4 encontrada: ID {$stage4->id} - fin {$stage4->end_date}"
        );

        /*
        * 2) La temporada debe tener su RankingHistory final.
        */
        $historyCount = RankingHistory::query()
            ->where('season', $season)
            ->count();

        if ($historyCount === 0) {
            $this->error(
                "No existe RankingHistory final para la temporada {$season}."
            );

            return self::FAILURE;
        }

        $this->info(
            "RankingHistory {$season}: {$historyCount} jugadores."
        );

        /*
        * El baseline solamente puede construirse para la temporada
        * actualmente representada por el Ranking General.
        *
        * Utilizamos exactamente el mismo criterio de selección de etapas
        * que RankingService: la última etapa finalizada de cada número
        * de etapa (1, 2, 3 y 4).
        */
        $currentRankingTournaments = collect();

        foreach ([1, 2, 3, 4] as $stage) {
            $tournament = Tournament::query()
                ->whereHas('type', fn ($query) =>
                    $query->where('affects_ranking', true)
                )
                ->where('stage_number', $stage)
                ->where('end_date', '<=', now())
                ->orderByDesc('end_date')
                ->first();

            if ($tournament) {
                $currentRankingTournaments->push($tournament);
            }
        }

        if ($currentRankingTournaments->isEmpty()) {
            $this->error(
                'No se pudo determinar la temporada vigente del Ranking General.'
            );

            return self::FAILURE;
        }

        $latestCurrentTournament = $currentRankingTournaments
            ->sortByDesc('end_date')
            ->first();

        $currentRankingSeason = (int) $latestCurrentTournament->end_date->year;

        $this->info(
            "Temporada actualmente representada por el Ranking General: {$currentRankingSeason}."
        );

        if ($currentRankingSeason !== $season) {
            $this->error(
                "No se puede crear el baseline {$season}: el Ranking General actual corresponde a la temporada {$currentRankingSeason}."
            );

            return self::FAILURE;
        }

        /*
        * 3) No permitimos inicializar sobre una temporada que ya tenga
        *    historial temporal de ranking.
        */
        $existingRankingHistory = PlayerCategoryHistory::query()
            ->where('season', $season)
            ->where('source', 'ranking')
            ->exists();

        if ($existingRankingHistory) {
            $this->error(
                "La temporada {$season} ya posee historial de categorías de ranking."
            );

            return self::FAILURE;
        }

        /*
        * 4) Obtener el estado temporal M/N actualmente publicado.
        *
        * La existencia de Etapa 4 finalizada y RankingHistory de la misma
        * temporada ya fue validada arriba. GeneralRanking aporta el estado
        * temporal M/N que debemos tomar como baseline.
        */
        $temporaryRows = GeneralRanking::query()
            ->whereIn('category', ['M', 'N'])
            ->orderBy('RG')
            ->get();

        if ($temporaryRows->isEmpty()) {
            $this->error(
                'El Ranking General actual no contiene jugadores M/N.'
            );

            return self::FAILURE;
        }

        $masterCount = $temporaryRows
            ->where('category', 'M')
            ->count();

        $nationalCount = $temporaryRows
            ->where('category', 'N')
            ->count();

        $this->info(
            "Ranking General actual: {$masterCount} M + {$nationalCount} N."
        );

        /*
        * 5) Validar las categorías temporales M y N.
        */
        $categories = Category::query()
            ->whereIn('code', ['M', 'N'])
            ->get()
            ->keyBy('code');

        if (!$categories->has('M') || !$categories->has('N')) {
            $this->error(
                'No existen correctamente las categorías M y N en la base de datos.'
            );

            return self::FAILURE;
        }

        /*
        * Todos los registros temporales deben estar asociados
        * a un jugador real.
        */
        $rowsWithoutPlayer = $temporaryRows
            ->filter(fn ($row) => !$row->player_id);

        if ($rowsWithoutPlayer->isNotEmpty()) {
            $this->error(
                'Existen jugadores M/N en GeneralRanking sin player_id.'
            );

            return self::FAILURE;
        }

        $uniquePlayerCount = $temporaryRows
            ->pluck('player_id')
            ->unique()
            ->count();

        if ($uniquePlayerCount !== $temporaryRows->count()) {
            $this->error(
                'Existen player_id duplicados entre los jugadores M/N del Ranking General.'
            );

            return self::FAILURE;
        }

        /*
        * Todos los jugadores M/N utilizados para el baseline deben existir
        * en la fotografía final de RankingHistory de la misma temporada.
        *
        * No exigimos que RankingHistory.category sea M/N porque esa columna
        * histórica puede contener otra clasificación. Solamente verificamos
        * que el jugador pertenezca efectivamente al ranking final cerrado.
        */
        $finalRankingPlayerIds = RankingHistory::query()
            ->where('season', $season)
            ->whereIn(
                'player_id',
                $temporaryRows->pluck('player_id')->all()
            )
            ->pluck('player_id')
            ->map(fn ($playerId) => (int) $playerId)
            ->unique();

        $missingFromFinalHistory = $temporaryRows
            ->pluck('player_id')
            ->map(fn ($playerId) => (int) $playerId)
            ->unique()
            ->diff($finalRankingPlayerIds);

        if ($missingFromFinalHistory->isNotEmpty()) {
            $this->error(
                'Existen jugadores M/N del Ranking General que no pertenecen al RankingHistory final de la temporada.'
            );

            $this->line(
                'Player ID faltantes: ' . $missingFromFinalHistory->implode(', ')
            );

            return self::FAILURE;
        }

        /*
        * El baseline tiene vigencia desde el cierre de la Etapa 4.
        */
        $effectiveDate = $stage4->end_date->toDateString();

        $this->info(
            "Fecha efectiva del baseline: {$effectiveDate}."
        );

        $this->info(
            "Registros a crear: {$temporaryRows->count()}."
        );

        if ($this->option('dry-run')) {
            $this->warn(
                'DRY RUN: validación completada. No se creó ningún registro.'
            );

            return self::SUCCESS;
        }

        /*
        * 6) Crear el estado base.
        *
        * previous_category_id queda NULL deliberadamente porque estos
        * registros representan un estado inicial conocido, no un cambio
        * histórico ocurrido en ese momento.
        */
        DB::transaction(function () use (
            $temporaryRows,
            $categories,
            $season,
            $effectiveDate
        ) {
            foreach ($temporaryRows as $row) {
                $category = $categories->get($row->category);

                if (!$category) {
                    throw new \RuntimeException(
                        "No existe la categoría {$row->category}."
                    );
                }

                PlayerCategoryHistory::create([
                    'player_id' => $row->player_id,
                    'season' => $season,
                    'category_id' => $category->id,
                    'change_type' => 'ranking',
                    'previous_category_id' => null,
                    'source' => 'ranking',
                    'tournament_id' => null,
                    'ranking_id' => null,
                    'effective_date' => $effectiveDate,
                    'applied_at' => now(),
                    'reason' => 'Estado base de categoría temporal al cierre de la temporada',
                    'notes' => "Baseline inicial del Ranking General {$season}",
                ]);
            }
        });

        $this->info(
            "Baseline {$season} creado correctamente: {$temporaryRows->count()} registros."
        );

        return self::SUCCESS;
    }
}
