<?php

namespace App\Services;

use App\Models\GeneralRanking;
use App\Models\Player;
use App\Models\RankingHistory;
use App\Models\Tournament;
use App\Models\TournamentRegistration;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use App\Models\RankingSpecialPosition;

class RankingService
{
    /**
     * Devuelve la colección formateada para el ranking general.
     *
     * @return \Illuminate\Support\Collection
     */
    public static function getGeneralRanking(?string $category = null, ?string $search = null): Collection
    {
        // 1) Últimos 4 torneos que afectan ranking
        /*$torneos = Tournament::query()
            ->whereHas('type', fn ($q) => $q->where('affects_ranking', true))
            ->where('end_date', '<=', now())
            ->orderByDesc('end_date') // ajustá al campo de fecha real
            ->take(4)
            ->pluck('id'); */

        $torneos = collect();

        foreach ([1, 2, 3, 4] as $stage) {
            $torneo = Tournament::query()
                ->whereHas('type', fn ($q) => $q->where('affects_ranking', true))
                ->where('stage_number', $stage)
                ->where('end_date', '<=', now())
                ->orderByDesc('end_date') // toma el más reciente de esa etapa
                ->first();

            if ($torneo) {
                $torneos->push($torneo->id);
            }
        }

        if ($torneos->isEmpty()) {
            return collect();
        }

        // 2) Inscripciones de esos torneos
        $regs = TournamentRegistration::query()
            ->with([
                'player.club.city.state.federation',
                'player.category',
                'tournamentInstance',
            ])
            ->whereIn('tournament_id', $torneos)
            ->get();

        // 3) Jugadores con puntos en alguno de los torneos seleccionados
        $jugadores = Player::query()
            ->whereHas('registrations', function ($q) use ($torneos) {
                $q->whereIn('tournament_id', $torneos)
                    ->where('points', '>', 0);
            })
            ->with(['club.city.state.federation', 'category'])
            ->get();

        // Ranking anterior:
        // Es la fotografía del ranking final guardada al terminar
        // la Etapa 4 de la temporada anterior.

        $selectedTournaments = Tournament::query()
            ->findMany($torneos);

        $latestTournament = $selectedTournaments
            ->sortByDesc('end_date')
            ->first();

        $currentSeason = $latestTournament->end_date->year;

        $stage4Tournament = $selectedTournaments
            ->firstWhere('stage_number', 4);

        $isSeasonClosed = $stage4Tournament
            && $stage4Tournament->end_date->year === $currentSeason;

        // Límites reglamentarios configurables
        $masterMaxRG = (int) config('ranking.master_max_rg', 16);
        $nationalMinRG = $masterMaxRG + 1;
        $nationalMaxRGNormal = (int) config('ranking.national_max_rg', 48);
        $nationalMaxRGAfterStage4 = (int) config('ranking.national_max_rg_after_stage_4', 46);

        $previousSeason = $currentSeason - 1;

        $rankingAnterior = RankingHistory::where('season', $previousSeason)
            ->pluck('RG', 'player_id');

        // 4) Construir estructura por jugador
        $ranking = $jugadores->map(function ($player) use ($regs, $torneos, $rankingAnterior) {

            // Inscripciones del jugador en los 4 torneos seleccionados
            $items = $regs->where('player_id', $player->id);

            // Ordenar por el orden de los torneos seleccionados
            $ordenados = collect($torneos)->map(fn ($tid) => $items->firstWhere('tournament_id', $tid));

            // A. Suma total de puntos obtenidos en las 4 etapas
            $puntos_brutos = $ordenados->sum(fn ($r) => $r?->points ?? 0);
            
            // B. Suma total de penalizaciones (multas)
            $multas = $ordenados->sum(fn ($r) => $r?->penalty_points ?? 0);

            // C. TOTAL NETO: El primer criterio de ordenación (Puntos - Multas)
            $total_neto = $puntos_brutos - $multas;           

            return [
                'player' => $player,
                'total' => $total_neto,
                'total_penalties' => $multas,
                // Guardamos las instancias (valores numéricos de posición)
                'posiciones' => $ordenados->map(fn ($r) => $r?->tournamentInstance?->instance ?? 0),
                'detalle' => $ordenados->map(fn ($r) => [
                    'description' => $r?->tournamentInstance?->description ?? null,
                    'ptos' => $r?->points ?? null,
                ]),
                'previous_rank' => $rankingAnterior[$player->id] ?? PHP_INT_MAX,
            ];
        });

        // 5) Ordenar según reglas:
        // 1) mayor total
        // 2) mejor posición (mayor instance)
        // 3) segunda mejor, etc.
        // --- Paso 5: Ordenar el ranking ---
        $rankingOrdenado = $ranking->sort(function ($a, $b) {
            // 1) Primero comparamos el total neto
            if ($a['total'] !== $b['total']) {
                return $b['total'] <=> $a['total'];
            }

            // 2) DESEMPATE: Mejor posición absoluta (Lógica K.ESIMO.MAYOR)
            // Extraemos las posiciones, filtramos nulos y las ORDENAMOS de mejor a peor
            // Nota: Se usa sortDesc() porque en tu lógica una instancia mayor es un mejor puesto.
            $mejoresA = collect($a['posiciones'])->filter()->sortDesc()->values();
            $mejoresB = collect($b['posiciones'])->filter()->sortDesc()->values();

            // Comparamos las mejores posiciones una por una
            for ($i = 0; $i < 4; $i++) {
                $valA = $mejoresA->get($i, 0); // Toma el mejor resultado de A
                $valB = $mejoresB->get($i, 0); // Toma el mejor resultado de B

                if ($valA !== $valB) {
                    return $valB <=> $valA; // El que tenga la mejor posición absoluta queda arriba
                }
            }

           return $a['previous_rank'] <=> $b['previous_rank'];
        })->values();

        /*
        |--------------------------------------------------------------------------
        | Campeonato Argentino de Primera Categoría
        |--------------------------------------------------------------------------
        */

        $specialPosition = null;

        if ($isSeasonClosed) {
            $specialPosition = RankingSpecialPosition::query()
                ->where('season', $currentSeason)
                ->first();
        }

        if ($specialPosition) {

            $championId = (int) $specialPosition->champion_player_id;
            $runnerUpId = (int) $specialPosition->runner_up_player_id;

            $championItem = $rankingOrdenado
                ->first(fn ($item) => (int) $item['player']->id === $championId);

            $runnerUpItem = $rankingOrdenado
                ->first(fn ($item) => (int) $item['player']->id === $runnerUpId);

            if (!$championItem) {
                $player = Player::query()
                    ->with(['club.city.state.federation', 'category'])
                    ->find($championId);

                if ($player) {
                    $championItem = [
                        'player' => $player,
                        'total' => 0,
                        'total_penalties' => 0,
                        'posiciones' => collect([0, 0, 0, 0]),
                        'detalle' => collect([
                            ['description' => null, 'ptos' => null],
                            ['description' => null, 'ptos' => null],
                            ['description' => null, 'ptos' => null],
                            ['description' => null, 'ptos' => null],
                        ]),
                        'previous_rank' => $rankingAnterior[$player->id] ?? PHP_INT_MAX,
                    ];
                }
            }

            if (!$runnerUpItem) {
                $player = Player::query()
                    ->with(['club.city.state.federation', 'category'])
                    ->find($runnerUpId);

                if ($player) {
                    $runnerUpItem = [
                        'player' => $player,
                        'total' => 0,
                        'total_penalties' => 0,
                        'posiciones' => collect([0, 0, 0, 0]),
                        'detalle' => collect([
                            ['description' => null, 'ptos' => null],
                            ['description' => null, 'ptos' => null],
                            ['description' => null, 'ptos' => null],
                            ['description' => null, 'ptos' => null],
                        ]),
                        'previous_rank' => $rankingAnterior[$player->id] ?? PHP_INT_MAX,
                    ];
                }
            }

            if ($championItem && $runnerUpItem) {

                $restoRanking = $rankingOrdenado
                    ->reject(function ($item) use ($championId, $runnerUpId) {
                        $playerId = (int) $item['player']->id;

                        return $playerId === $championId
                            || $playerId === $runnerUpId;
                    })
                    ->values();

                $primerosNacionalesBase = $restoRanking
                    ->take($nationalMaxRGAfterStage4);

                $desdeSiguiente = $restoRanking
                    ->skip($nationalMaxRGAfterStage4);

                $rankingOrdenado = $primerosNacionalesBase
                    ->concat([
                        $championItem,
                        $runnerUpItem,
                    ])
                    ->concat($desdeSiguiente)
                    ->values();
            }
        }

        $nationalMaxRG = ($isSeasonClosed && !$specialPosition)
            ? $nationalMaxRGAfterStage4
            : $nationalMaxRGNormal;

        // 6) Agregar RG y RC
        $rankingFinal = $rankingOrdenado->map(function ($item, $index) use (
            $rankingOrdenado,
            $nationalMaxRG,
            $masterMaxRG,
            $nationalMinRG
        ) {
            $player = $item['player'];

            // Ranking general
            $item['RG'] = $index + 1;

            // Calcular categoría según RG
            if ($item['RG'] <= $masterMaxRG) {
                $item['nivel'] = 'M';
            } elseif (
                $item['RG'] >= $nationalMinRG
                && $item['RG'] <= $nationalMaxRG
            ) {
                $item['nivel'] = 'N';
            } else {
                $item['nivel'] = $player->category->code ?? null;
            }

            // Calcular RC dentro de cada categoría
            $item['RC'] = $rankingOrdenado
                ->map(function ($r, $i) use (
                    $nationalMaxRG,
                    $masterMaxRG,
                    $nationalMinRG
                ) {
                    $rg = $i + 1;

                    if ($rg <= $masterMaxRG) {
                        $nivel = 'M';
                    } elseif (
                        $rg >= $nationalMinRG
                        && $rg <= $nationalMaxRG
                    ) {
                        $nivel = 'N';
                    } else {
                        $nivel = $r['player']->category->code ?? null;
                    }

                    return [
                        'nivel' => $nivel,
                        'index' => $i,
                    ];
                })
                ->filter(fn ($r) => $r['nivel'] === $item['nivel'])
                ->pluck('index')
                ->search($index) + 1;

            return $item;
        });

        // 7) Formato final para tabla
        $data =  $rankingFinal->map(function ($item) {

            $player = $item['player'];

            $detalle = collect($item['detalle']);

            return [
                // Relacion con Player
                'player_id' => $player->id,

                // Ranking
                'RG' => $item['RG'],
                'RC' => $item['RC'],

                //Datos deportivos
                'category' => $item['nivel'],

                // Datos del jugador
                'last_name' => $player->last_name,
                'first_name' => $player->first_name,
                'club' => $player->club?->name ?? null,
                'fed' => $player->club?->city?->state?->federation?->short_name ?? 'SIN FED',

                // Puntos
                'total_puntos' => $item['total'],
                'total_penalties' => $item['total_penalties'] ?? 0,

                // Detalle de las etapas
                'pos_1' => $detalle->get(0)['description'] ?? null,
                'ptos_1' => $detalle->get(0)['ptos'] ?? null,

                'pos_2' => $detalle->get(1)['description'] ?? null,
                'ptos_2' => $detalle->get(1)['ptos'] ?? null,

                'pos_3' => $detalle->get(2)['description'] ?? null,
                'ptos_3' => $detalle->get(2)['ptos'] ?? null,

                'pos_4' => $detalle->get(3)['description'] ?? null,
                'ptos_4' => $detalle->get(3)['ptos'] ?? null,
            ];
        });

        // --- APLICAR FILTROS MANUALMENTE ---
    
        // Filtrar por Categoría (SelectFilter)
        if ($category) {
            $data = $data->where('category', $category);
        }

        // Filtrar por Búsqueda (Searchable)
        if ($search) {
            $search = strtolower($search);
            $data = $data->filter(function ($item) use ($search) {
                return str_contains(strtolower($item['last_name'] ?? ''), $search) || 
                    str_contains(strtolower($item['club'] ?? ''), $search);
            });
        }

        return $data->values(); // Resetear índices para evitar problemas de renderizado
    }

    public static function syncGeneralRanking(): void
    {
        // Primero calculamos completamente el nuevo ranking.
        // Todavía no modificamos general_rankings.
        $data = self::getGeneralRanking();

        // Seguridad 1: nunca vaciar el ranking si el cálculo no produjo jugadores.
        if ($data->isEmpty()) {
            throw new \RuntimeException(
                'No se puede sincronizar el Ranking General porque el cálculo devolvió 0 jugadores.'
            );
        }

        // Seguridad 2: todas las filas deben estar vinculadas a un jugador.
        if ($data->contains(fn ($row) => empty($row['player_id']))) {
            throw new \RuntimeException(
                'No se puede sincronizar el Ranking General porque existen filas sin player_id.'
            );
        }

        // Sólo después de superar las validaciones reemplazamos el ranking.
        // Si cualquier create() falla, la transacción revierte también el delete().
        DB::transaction(function () use ($data) {
            GeneralRanking::query()->delete();

            foreach ($data as $row) {
                GeneralRanking::create($row);
            }
        });
    }

    /**
     * Guarda una fotografía del Ranking General al cierre de una temporada.
     *
     * @param int $season
     * @return void
     */
    public static function saveSeasonRanking(int $season): void
    {
        $torneos = collect();

        foreach ([1, 2, 3, 4] as $stage) {
            $torneo = Tournament::query()
                ->whereHas('type', fn ($q) => $q->where('affects_ranking', true))
                ->where('stage_number', $stage)
                ->where('end_date', '<=', now())
                ->orderByDesc('end_date')
                ->first();

            if ($torneo) {
                $torneos->push($torneo);
            }
        }

        if ($torneos->count() !== 4) {
            throw new \RuntimeException(
                'No se puede cerrar la temporada: no existen las 4 etapas finalizadas.'
            );
        }

        $latestTournament = $torneos
            ->sortByDesc('end_date')
            ->first();

        $currentSeason = $latestTournament->end_date->year;

        $stage4Tournament = $torneos
            ->firstWhere('stage_number', 4);

        if (
            !$stage4Tournament ||
            $stage4Tournament->end_date->year !== $currentSeason
        ) {
            throw new \RuntimeException(
                'No se puede cerrar la temporada: la Etapa 4 de la temporada vigente todavía no está finalizada.'
            );
        }

        if ($currentSeason !== $season) {
            throw new \RuntimeException(
                "La temporada vigente es {$currentSeason}, no {$season}."
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Proteger el histórico del Campeonato Argentino
        |--------------------------------------------------------------------------
        |
        | Las posiciones RG47 y RG48 del Campeonato Argentino son transitorias
        | y no deben formar parte del RankingHistory de cierre de temporada.
        |
        | Por lo tanto, si ya fueron asignadas, impedimos regenerar el histórico.
        |
        */

        if (
            RankingSpecialPosition::query()
                ->where('season', $season)
                ->exists()
        ) {
            throw new \RuntimeException(
                "No se puede regenerar el histórico {$season}: ya existen posiciones especiales del Campeonato Argentino."
            );
        }

        $ranking = GeneralRanking::query()
            ->orderBy('RG')
            ->get();

        if ($ranking->isEmpty()) {
            throw new \RuntimeException(
                'No se puede guardar el histórico porque GeneralRanking está vacío.'
            );
        }

        if ($ranking->contains(fn ($row) => empty($row->player_id))) {
            throw new \RuntimeException(
                'No se puede guardar el histórico porque existen filas sin player_id.'
            );
        }

        DB::transaction(function () use ($season, $ranking) {

            RankingHistory::where('season', $season)->delete();

            foreach ($ranking as $row) {
                RankingHistory::create([
                    'season'          => $season,
                    'player_id'       => $row->player_id,
                    'RG'              => $row->RG,
                    'RC'              => $row->RC,
                    'category'        => $row->category,
                    'last_name'       => $row->last_name,
                    'first_name'      => $row->first_name,
                    'club'            => $row->club,
                    'fed'             => $row->fed,
                    'total_puntos'    => $row->total_puntos,
                    'total_penalties' => $row->total_penalties,
                    'pos_1'           => $row->pos_1,
                    'ptos_1'          => $row->ptos_1,
                    'pos_2'           => $row->pos_2,
                    'ptos_2'          => $row->ptos_2,
                    'pos_3'           => $row->pos_3,
                    'ptos_3'          => $row->ptos_3,
                    'pos_4'           => $row->pos_4,
                    'ptos_4'          => $row->ptos_4,
                ]);
            }
        });
    }   

}

