<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class MarkerApi
{
    public function configured(): bool
    {
        $url = parse_url((string) config('marker.url'));

        if ($this->isPilot() && (! preg_match('/^[a-f0-9]{64}$/D', (string) config('marker.pilot_key'))
            || ! preg_match('/^[1-9][0-9]*$/D', (string) config('marker.pilot_tournament_id')))) {
            return false;
        }

        return isset($url['host']) && ! isset($url['user']) && ! isset($url['pass'])
            && ! isset($url['query']) && ! isset($url['fragment'])
            && (($url['scheme'] ?? null) === 'https' || (($url['scheme'] ?? null) === 'http' && app()->environment('local', 'testing')));
    }

    public function availableFor(int $tournamentId): bool
    {
        return $this->configured()
            && (! $this->isPilot() || (int) config('marker.pilot_tournament_id') === $tournamentId);
    }

    private function isPilot(): bool
    {
        $host = parse_url((string) config('marker.url'), PHP_URL_HOST);

        return filled(config('marker.pilot_key')) || filled(config('marker.pilot_tournament_id'))
            || str_ends_with(strtolower((string) $host), '.trycloudflare.com');
    }

    public function request(string $method, string $path, array $payload = [], ?string $token = null): array
    {
        if (! $this->configured()) {
            throw new RuntimeException('Configurá la dirección del servidor de marcadores.');
        }

        try {
            $client = Http::acceptJson()->asJson()->withoutRedirecting()->connectTimeout(2)->timeout(4);
            if ($this->isPilot()) {
                $client = $client->withHeaders(['X-Marker-Pilot-Key' => config('marker.pilot_key')]);
            }
            if ($token !== null) {
                $client = $client->withToken($token);
            }
            $url = rtrim((string) config('marker.url'), '/').'/api/v1/fab'.$path;
            $response = $method === 'GET' ? $client->get($url) : $client->post($url, $payload);
        } catch (ConnectionException) {
            throw new RuntimeException('El servidor de marcadores no respondió.');
        }

        $body = $response->json();
        if (! $response->successful() || ! is_array($body)) {
            throw new RuntimeException($response->clientError() && is_string($body['error'] ?? null)
                ? $body['error'] : 'El servidor de marcadores devolvió una respuesta inválida.');
        }

        return $body;
    }
}
