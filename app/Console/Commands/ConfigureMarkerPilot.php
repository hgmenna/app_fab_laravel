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
        if (! preg_match('#^https://[a-z0-9-]+\.trycloudflare\.com$#D', $url)) {
            $this->error('Ingresá la dirección HTTPS temporal de Cloudflare.');

            return self::FAILURE;
        }
        if (! app()->environment('testing') && in_array(config('cache.default'), ['array', 'null'], true)) {
            $this->error('La prueba necesita un almacenamiento de caché compartido entre Artisan y la web.');

            return self::FAILURE;
        }

        $key = trim((string) $this->secret('Clave temporal del puente'));
        if (! preg_match('/^[a-f0-9]{64}$/D', $key)) {
            $this->error('La clave temporal debe tener 64 caracteres hexadecimales.');

            return self::FAILURE;
        }
        try {
            if (! $api->verifyPilot($url, $key) || ! MarkerPilot::activate($tournament, $url, $key)) {
                $this->error('No se pudo verificar o guardar la conexión de prueba.');

                return self::FAILURE;
            }
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info('Conexión verificada. Prueba habilitada por cuatro horas para el torneo '.$tournament.'.');

        return self::SUCCESS;
    }
}
