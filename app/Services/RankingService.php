<?php

namespace App\Services;

use App\Models\Tournament;
use App\Models\TournamentRegistration;
use App\Models\Player;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\delete;

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

        // 3) Jugadores con más de 0 puntos (en cualquier torneo)
        $jugadores = Player::query()
            ->whereHas('registrations', fn ($q) => $q->where('points', '>', 0))
            ->with(['club.city.state.federation', 'category'])
            ->get();

        // 4) Construir estructura por jugador
        $ranking = $jugadores->map(function ($player) use ($regs, $torneos) {

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

            return 0;
        })->values();

        // 6) Agregar RG y RC
        $rankingFinal = $rankingOrdenado->map(function ($item, $index) use ($rankingOrdenado) {

    $player = $item['player'];

    // Ranking general
    $item['RG'] = $index + 1;

    // Calcular nivel según RG
    if ($item['RG'] <= 16) {
        $item['nivel'] = 'M';
    } elseif ($item['RG'] >= 17 && $item['RG'] <= 48) {
        $item['nivel'] = 'N';
    } else {
        $item['nivel'] = $player->category->code ?? null;
    }

    // Calcular RC usando nivel
    $item['RC'] = $rankingOrdenado
        ->map(function ($r, $i) {
            // calcular nivel para cada jugador
            $rg = $i + 1;

            if ($rg <= 16) {
                $nivel = 'M';
            } elseif ($rg >= 17 && $rg <= 48) {
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
                'RG' => $item['RG'],
                'RC' => $item['RC'],
                'category' => $item['nivel'],
                'last_name' => $player->last_name,
                'first_name' => $player->first_name,
                'club' => $player->club?->name ?? null,
                'fed' => $player->club?->city?->state?->federation?->short_name ?? 'SIN FED',
                'total_puntos' => $item['total'],
                'total_penalties' => $item['total_penalties'] ?? 0,

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
        // Obtenemos la colección que ya procesa tu lógica [3, 8]
        $data = self::getGeneralRanking();

        DB::transaction(function () use ($data) {
            \App\Models\GeneralRanking::query()->delete(); // Limpia el ranking anterior
            foreach ($data as $row) {
                \App\Models\GeneralRanking::create($row);
            }
        });
    }

    
}

