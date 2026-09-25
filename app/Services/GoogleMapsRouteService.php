<?php

namespace App\Services;

use App\Models\Club;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class GoogleMapsRouteService
{
    public function distanceInMeters(Club $origin, Club $destination): array
    {
        $apiKey = config('services.google_maps.routes_api_key');

        if (! $apiKey) {
            throw new RuntimeException('No está configurada la clave de Google Maps Routes API.');
        }

        $originAddress = $this->fullAddress($origin);
        $destinationAddress = $this->fullAddress($destination);

        $response = Http::timeout(20)
            ->retry(2, 300)
            ->withHeaders([
                'X-Goog-Api-Key' => $apiKey,
                'X-Goog-FieldMask' => 'routes.distanceMeters,routes.duration,routes.description',
            ])
            ->post('https://routes.googleapis.com/directions/v2:computeRoutes', [
                'origin' => ['address' => $originAddress],
                'destination' => ['address' => $destinationAddress],
                'travelMode' => 'DRIVE',
                'routingPreference' => 'TRAFFIC_UNAWARE',
                'computeAlternativeRoutes' => false,
                'routeModifiers' => [
                    'avoidTolls' => false,
                    'avoidHighways' => false,
                    'avoidFerries' => false,
                ],
                'languageCode' => 'es-AR',
                'units' => 'METRIC',
            ]);

        if ($response->failed()) {
            throw new RuntimeException('Google Maps no pudo calcular la ruta: '.$response->status().'.');
        }

        $route = $response->json('routes.0');

        if (! is_array($route) || ! isset($route['distanceMeters'])) {
            throw new RuntimeException('Google Maps no encontró una ruta en automóvil entre los clubes.');
        }

        return [
            'distance_meters' => (int) $route['distanceMeters'],
            'duration' => $route['duration'] ?? null,
            'description' => $route['description'] ?? null,
            'origin_address' => $originAddress,
            'destination_address' => $destinationAddress,
            'provider' => 'Google Maps Routes API',
            'calculated_at' => now()->toIso8601String(),
        ];
    }

    public function fullAddress(Club $club): string
    {
        $club->loadMissing('city.state.country');

        $parts = array_filter([
            trim((string) $club->address),
            $club->city?->name,
            $club->city?->state?->name,
            $club->city?->state?->country?->name ?: 'Argentina',
        ]);

        if (! $club->address || ! $club->city?->name || ! $club->city?->state?->name) {
            throw new RuntimeException("El club {$club->name} no tiene domicilio, localidad y provincia completos.");
        }

        return implode(', ', $parts);
    }
}
