<?php

namespace App\Console\Commands;

use App\Models\Player;
use App\Models\RankingHistory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportRankingHistoryCommand extends Command
{
    protected $signature = 'ranking:import-history
                        {file : Archivo dentro de storage/app}
                        {season : Temporada histórica}
                        {--dry-run : Analiza el archivo sin modificar la base de datos}';

    protected $description = 'Importa un Ranking Histórico desde Excel';

    public function handle()
    {
        $file = storage_path('app/' . $this->argument('file'));
        $season = (int) $this->argument('season');
        $dryRun = (bool) $this->option('dry-run');

        if (! file_exists($file)) {
            $this->error('No existe el archivo: ' . $file);
            return self::FAILURE;
        }

        $this->info('======================================');
        $this->info('IMPORTACION RANKING HISTORICO');
        $this->info('======================================');
        $this->info('Archivo   : ' . $file);
        $this->info('Temporada : ' . $season);

        if ($dryRun) {
            $this->warn('MODO DRY-RUN: no se modificará la base de datos.');
        }

        try {
            $spreadsheet = IOFactory::load($file);
        } catch (\Throwable $e) {
            $this->error('No se pudo abrir el archivo Excel.');
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $sheet = $spreadsheet->getSheetByName('Ranking');

        if (! $sheet) {
            $this->error("No existe la hoja 'Ranking'.");

            return self::FAILURE;
        }

        $rows = $sheet->toArray(null, true, true, false);

        /*
        |--------------------------------------------------------------------------
        | Buscar cabecera
        |--------------------------------------------------------------------------
        */

        $headerIndex = null;

        foreach ($rows as $index => $row) {
            if (
                isset($row[0], $row[1], $row[2]) &&
                trim((string) $row[0]) === 'G' &&
                trim((string) $row[1]) === 'C' &&
                str_contains(
                    strtoupper(trim((string) $row[2])),
                    'APELLIDO'
                )
            ) {
                $headerIndex = $index;
                break;
            }
        }

        if ($headerIndex === null) {
            $this->error('No se encontró la cabecera del Ranking.');

            return self::FAILURE;
        }

        $this->info(
            'Cabecera encontrada en fila Excel: ' .
            ($headerIndex + 1)
        );

        /*
        |--------------------------------------------------------------------------
        | Jugadores activos
        |--------------------------------------------------------------------------
        */

        $players = Player::query()
            ->with([
                'club.city.state.federation',
                'category',
            ])
            ->get();

        $this->info(
            'Jugadores en BD: ' .
            $players->count()
        );

        /*
        |--------------------------------------------------------------------------
        | Crear índice por nombre normalizado
        |--------------------------------------------------------------------------
        */

        $playersIndex = [];

        foreach ($players as $player) {
            $nombreCompleto = trim(
                $player->last_name . ' ' .
                $player->first_name
            );

            $key = $this->normalize($nombreCompleto);

            if ($key === '') {
                continue;
            }

            $playersIndex[$key][] = $player;
        }

        /*
        |--------------------------------------------------------------------------
        | Filas reales de ranking
        |--------------------------------------------------------------------------
        */

        $rankingRows = array_slice(
            $rows,
            $headerIndex + 1
        );

        $totalExcel = 0;
        $importados = 0;
        $duplicados = 0;
        $noEncontrados = 0;

        $duplicadosLista = [];
        $noEncontradosLista = [];

        $existentes2025 = RankingHistory::where('season', $season)
            ->pluck('player_id')
            ->all();

        $nuevosDetectados = [];

        /*
        |--------------------------------------------------------------------------
        | Importación
        |--------------------------------------------------------------------------
        */

        DB::transaction(function () use (
            $rankingRows,
            $playersIndex,
            $season,
            $dryRun,
            $existentes2025,
            &$nuevosDetectados,
            &$totalExcel,
            &$importados,
            &$duplicados,
            &$noEncontrados,
            &$duplicadosLista,
            &$noEncontradosLista
        ) {
            /*
            |----------------------------------------------------------------------
            | Eliminar historial previo de la temporada
            |----------------------------------------------------------------------
            */

            if (! $dryRun) {
                RankingHistory::where(
                    'season',
                    $season
                )->delete();
            }

            foreach ($rankingRows as $row) {

                /*
                |--------------------------------------------------------------
                | Nombre Excel
                |--------------------------------------------------------------
                */

                $nombreExcel = trim(
                    (string) ($row[2] ?? '')
                );

                if ($nombreExcel === '') {
                    continue;
                }

                /*
                |--------------------------------------------------------------
                | RG
                |--------------------------------------------------------------
                */

                $rg = $this->numero(
                    $row[0] ?? null
                );

                /*
                |--------------------------------------------------------------
                | Ignorar filas que no sean jugadores reales
                |--------------------------------------------------------------
                */

                if ($rg === null) {
                    continue;
                }

                $totalExcel++;

                /*
                |--------------------------------------------------------------
                | Buscar jugador por nombre
                |--------------------------------------------------------------
                */

                $key = $this->normalize(
                    $nombreExcel
                );

                $candidatos =
                    $playersIndex[$key] ?? [];

                /*
                |--------------------------------------------------------------
                | No encontrado
                |--------------------------------------------------------------
                */

                if (count($candidatos) === 0) {

                    $noEncontrados++;

                    $noEncontradosLista[] = [
                        'nombre' => $nombreExcel,
                        'institucion' => trim(
                            (string) ($row[3] ?? '')
                        ),
                        'RG' => $rg,
                    ];

                    continue;
                }

                /*
                |--------------------------------------------------------------
                | Coincidencia única
                |--------------------------------------------------------------
                */

                if (count($candidatos) === 1) {

                    $player = $candidatos[0];

                    if (! in_array($player->id, $existentes2025)) {
                        $nuevosDetectados[] = [
                            'id' => $player->id,
                            'nombre' => $player->last_name . ' ' . $player->first_name,
                            'activo' => $player->is_active ? 'SI' : 'NO',
                            'rg' => $row[0] ?? null,
                        ];
                    }

                    if (! $dryRun) {
                        $this->guardarHistorial(
                            $player,
                            $row,
                            $season
                        );
                    }

                    $importados++;

                    continue;
                }

                /*
                |--------------------------------------------------------------
                | Hay más de un jugador con el mismo nombre.
                | Intentar resolver por club / institución.
                |--------------------------------------------------------------
                */

                $institucionExcel = $this->normalize(
                    (string) ($row[3] ?? '')
                );

                $coincidenciasClub = [];

                foreach ($candidatos as $player) {

                    $clubBD = $this->normalize(
                        $player->club?->name ?? ''
                    );

                    if (
                        $institucionExcel !== '' &&
                        $clubBD !== '' &&
                        (
                            str_contains(
                                $institucionExcel,
                                $clubBD
                            )
                            ||
                            str_contains(
                                $clubBD,
                                $institucionExcel
                            )
                        )
                    ) {
                        $coincidenciasClub[] =
                            $player;
                    }
                }

                /*
                |--------------------------------------------------------------
                | Club resolvió el duplicado
                |--------------------------------------------------------------
                */

                if (
                    count($coincidenciasClub) === 1 
                ) {

                    $player = $coincidenciasClub[0];

                    if (! in_array($player->id, $existentes2025)) {
                        $nuevosDetectados[] = [
                            'id' => $player->id,
                            'nombre' => $player->last_name . ' ' . $player->first_name,
                            'activo' => $player->is_active ? 'SI' : 'NO',
                            'rg' => $row[0] ?? null,
                        ];
                    }

                    if (! $dryRun) {
                        $this->guardarHistorial(
                            $player,
                            $row,
                            $season
                        );
                    }

                    $importados++;

                    continue;
                }

                /*
                |--------------------------------------------------------------
                | Duplicado sin resolver
                |--------------------------------------------------------------
                */

                // Caso histórico 2025 verificado manualmente:
                // FERNANDEZ DANIEL - RG 197 corresponde al player_id 873.
                if (
                    $season === 2025
                    && $this->normalize($nombreExcel) === 'FERNANDEZ DANIEL'
                    && (int) $rg === 197
                ) {
                    $player = collect($candidatos)->firstWhere('id', 873);

                    if ($player) {
                        if (! in_array($player->id, $existentes2025)) {
                            $nuevosDetectados[] = [
                                'id' => $player->id,
                                'nombre' => $player->last_name . ' ' . $player->first_name,
                                'activo' => $player->is_active ? 'SI' : 'NO',
                                'rg' => $row[0] ?? null,
                            ];
                        }

                        if (! $dryRun) {
                            $this->guardarHistorial(
                                $player,
                                $row,
                                $season
                            );
                        }

                        $importados++;

                        continue;
                    }
                }

                $duplicados++;

                $duplicadosLista[] = [
                    'nombre' => $nombreExcel,
                    'institucion' => trim(
                        (string) ($row[3] ?? '')
                    ),
                    'RG' => $rg,

                    'candidatos' =>
                        collect($candidatos)
                            ->map(
                                function ($player) {
                                    return [
                                        'id' =>
                                            $player->id,

                                        'club' =>
                                            $player
                                                ->club
                                                ?->name,
                                    ];
                                }
                            )
                            ->values()
                            ->all(),
                ];
            }

             if (! $dryRun && $duplicados > 0) {
                throw new \RuntimeException(
                    "Importación cancelada: existen {$duplicados} duplicados sin resolver. No se modificó el ranking histórico."
                );
            }
        });

        /*
        |--------------------------------------------------------------------------
        | Resumen
        |--------------------------------------------------------------------------
        */

        $this->newLine();

        $this->info(
            '======================================'
        );

        $this->info(
            'IMPORTACION FINALIZADA'
        );

        $this->info(
            '======================================'
        );

        $this->info(
            'Filas de ranking Excel : ' .
            $totalExcel
        );

        $this->info(
            'Importados             : ' .
            $importados
        );

        $this->info(
            'Duplicados             : ' .
            $duplicados
        );

        $this->info(
            'No encontrados         : ' .
            $noEncontrados
        );

        if (! empty($nuevosDetectados)) {

            $this->newLine();
            $this->warn('======================================');
            $this->warn('NUEVOS JUGADORES DETECTADOS');
            $this->warn('======================================');

            foreach ($nuevosDetectados as $nuevo) {
                $this->line(
                    'Player ID: ' . $nuevo['id']
                    . ' | Nombre: ' . $nuevo['nombre']
                    . ' | Activo: ' . $nuevo['activo']
                    . ' | RG Excel: ' . $nuevo['rg']
                );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Mostrar duplicados
        |--------------------------------------------------------------------------
        */

        if (count($duplicadosLista) > 0) {

            $this->newLine();

            $this->warn(
                '======================================'
            );

            $this->warn(
                'DUPLICADOS SIN RESOLVER'
            );

            $this->warn(
                '======================================'
            );

            foreach (
                $duplicadosLista
                as $duplicado
            ) {

                $this->warn(
                    'Nombre: ' .
                    $duplicado['nombre']
                );

                $this->warn(
                    'Institucion Excel: ' .
                    (
                        $duplicado[
                            'institucion'
                        ] ?: '-'
                    )
                );

                $this->warn(
                    'RG: ' .
                    (
                        $duplicado['RG']
                        ?? '-'
                    )
                );

                foreach (
                    $duplicado['candidatos']
                    as $candidato
                ) {

                    $this->warn(
                        '  Player ID: ' .
                        $candidato['id'] .
                        ' | Club: ' .
                        (
                            $candidato['club']
                            ?? '-'
                        )
                    );
                }

                $this->newLine();
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Mostrar no encontrados
        |--------------------------------------------------------------------------
        */

        if (
            count(
                $noEncontradosLista
            ) > 0
        ) {

            $this->newLine();

            $this->warn(
                '======================================'
            );

            $this->warn(
                'NO ENCONTRADOS'
            );

            $this->warn(
                '======================================'
            );

            foreach (
                $noEncontradosLista
                as $item
            ) {

                $this->warn(
                    $item['nombre'] .
                    ' | Institucion: ' .
                    (
                        $item['institucion']
                        ?: '-'
                    ) .
                    ' | RG: ' .
                    (
                        $item['RG']
                        ?? '-'
                    )
                );
            }
        }

        return self::SUCCESS;
    }

    /*
    |--------------------------------------------------------------------------
    | Guardar snapshot histórico
    |--------------------------------------------------------------------------
    */

    private function guardarHistorial(
        Player $player,
        array $row,
        int $season
    ): void {

        RankingHistory::create([

            'season' => $season,

            'player_id' =>
                $player->id,

            'RG' =>
                $this->numero(
                    $row[0] ?? null
                ),

            'RC' =>
                $this->numero(
                    $row[1] ?? null
                ),

            'category' =>
                trim(
                    (string) (
                        $row[5] ?? ''
                    )
                ),

            'last_name' =>
                $player->last_name,

            'first_name' =>
                $player->first_name,

            'club' =>
                $player->club?->name,

            'fed' =>
                $player
                    ->club
                    ?->city
                    ?->state
                    ?->federation
                    ?->short_name
                ?? 'SIN FED',

            'total_puntos' =>
                $this->numero(
                    $row[7] ?? null
                ),

            'pos_1' =>
                trim(
                    (string) (
                        $row[8] ?? ''
                    )
                ) ?: null,

            'ptos_1' =>
                $this->numero(
                    $row[9] ?? null
                ),

            'pos_2' =>
                trim(
                    (string) (
                        $row[10] ?? ''
                    )
                ) ?: null,

            'ptos_2' =>
                $this->numero(
                    $row[11] ?? null
                ),

            'pos_3' =>
                trim(
                    (string) (
                        $row[12] ?? ''
                    )
                ) ?: null,

            'ptos_3' =>
                $this->numero(
                    $row[13] ?? null
                ),

            'pos_4' =>
                trim(
                    (string) (
                        $row[14] ?? ''
                    )
                ) ?: null,

            'ptos_4' =>
                $this->numero(
                    $row[15] ?? null
                ),

            'total_penalties' => 0,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Convertir valores numéricos Excel
    |--------------------------------------------------------------------------
    */

    private function numero($value)
    {
        if (
            $value === null ||
            $value === ''
        ) {
            return null;
        }

        if (is_numeric($value)) {
            return $value + 0;
        }

        $value = str_replace(
            ',',
            '.',
            (string) $value
        );

        return is_numeric($value)
            ? $value + 0
            : null;
    }

    /*
    |--------------------------------------------------------------------------
    | Normalizar nombres / clubes
    |--------------------------------------------------------------------------
    */

    private function normalize(
        string $text
    ): string {

        $text = mb_strtoupper(
            trim($text),
            'UTF-8'
        );

        $converted = iconv(
            'UTF-8',
            'ASCII//TRANSLIT//IGNORE',
            $text
        );

        if ($converted !== false) {
            $text = $converted;
        }

        $text = preg_replace(
            '/[^A-Z0-9 ]/',
            ' ',
            $text
        );

        $text = preg_replace(
            '/\s+/',
            ' ',
            $text
        );

        return trim($text);
    }
}