<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Club;
use Cheesegrits\FilamentGoogleMaps\Helpers\Geocoder;

class GeocodeClubs extends Command
{
    // El nombre con el que llamarás al comando
    protected $signature = 'app:geocode-clubs';
    protected $description = 'Geocodifica clubes con relaciones profundas (ciudad/estado/país)';

    public function handle()
    {
        $this->info('Iniciando geocodificación de clubes...');

        // Buscamos clubes sin coordenadas (lat es null o 0)
        Club::whereNull('lat')->orWhere('lat', 0)->chunk(100, function ($clubs) {
        foreach ($clubs as $club) {
            $addressParts = [
                $club->address,
                $club->city?->name,
                $club->city?->state?->name,
                $club->city?->state?->country?->name,
            ];
            
            $fullAddress = implode(', ', array_filter($addressParts));

            if (!empty($fullAddress)) {
                // Instanciamos el geocodificador para evitar el error de método no estático
                $geocoder = new Geocoder();
                $res = $geocoder->geocode($fullAddress);
                
                if ($res) {
                    $club->update([
                        'lat' => $res['lat'],
                        'lng' => $res['lng']
                    ]);
                    $this->line("ID {$club->id} OK: {$fullAddress}");
                } else {
                    $this->error("ID {$club->id} FALLÓ: {$fullAddress}");
                }
            }
            usleep(200000); 
        }
        });

        $this->info('Proceso finalizado.');
    }
}