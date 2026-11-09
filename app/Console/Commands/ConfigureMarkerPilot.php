<?php

namespace App\Console\Commands;

use App\Models\Tournament;
use App\Services\MarkerApi;
use App\Services\MarkerPilot;
use Illuminate\Console\Command;
use RuntimeException;

class ConfigureMarkerPilot extends Command
{
    protected $signature = 'marcador:piloto {torneo? : ID del torneo de prueba} {url? : Dirección HTTPS temporal} {--detener : Deshabilita la prueba}';

    protected $description = 'Habilita el marcador local para un torneo durante cuatro horas';

    public function handle(MarkerApi $api): int
    {
        if ($this->option('detener')) {
            MarkerPilot::stop();
            $this->info('Prueba del marcador deshabilitada.');

            return self::SUCCESS;
        }

        $tournament = filter_var($this->argument('torneo'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $url = rtrim((string) $this->argument('url'), '/');
        if (! $tournament || ! Tournament::query()->whereKey($tournament)->exists()) {
            $this->error('Indicá el ID de un torneo existente.');

            return self::FAILURE;
        }
        $key = (string) $this->secret('Clave temporal del puente');
        try {
            MarkerPilot::enable($tournament, $url, $key, $api);
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info('Conexión verificada. Prueba habilitada por cuatro horas para el torneo '.$tournament.'.');

        return self::SUCCESS;
    }
}
