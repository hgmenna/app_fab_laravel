<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class MarkerApi
{
    public function configured(): bool
    {
        return $this->validConnection($this->connection());
    }

    private function validConnection(array $connection): bool
    {
        $url = parse_url($connection['url']);

        if ($this->isPilot($connection) && (! preg_match('/^[a-f0-9]{64}$/D', $connection['key'])
            || ! preg_match('/^[1-9][0-9]*$/D', (string) $connection['tournament']))) {
            return false;
        }

        return isset($url['host']) && ! isset($url['user']) && ! isset($url['pass'])
            && ! isset($url['query']) && ! isset($url['fragment'])
            && (($url['scheme'] ?? null) === 'https' || (($url['scheme'] ?? null) === 'http' && app()->environment('local', 'testing')));
    }

    public function availableFor(int $tournamentId): bool
    {
        $connection = $this->connection();

        return $this->validConnection($connection)
            && (! $this->isPilot($connection) || (int) $connection['tournament'] === $tournamentId);
    }

    /** @return array<string, string>|null */
    public function pilotClubOptions(): ?array
    {
        if (! $this->isPilot($this->connection())) {
            return null;
        }

        return collect(explode(',', (string) config('marker.pilot_clubs')))
            ->map(fn (string $code): string => Str::lower(trim($code)))
            ->filter(fn (string $code): bool => preg_match('/^[a-z0-9][a-z0-9-]{0,63}$/D', $code) === 1)
            ->mapWithKeys(fn (string $code): array => [$code => Str::headline($code)])
            ->all();
    }

    private function connection(): array
    {
        $url = config('marker.url');
        if (blank($url)) {
            $productionHost = 'sistem.federacionargentinadebillar.org';
            if (app()->environment('production')
                || request()->getHost() === $productionHost
                || parse_url((string) config('app.url'), PHP_URL_HOST) === $productionHost) {
                $url = config('marker.production_url');
            } elseif (app()->environment('local')) {
                return ['url' => config('marker.local_url'), 'key' => '', 'tournament' => null];
            }
        }

        return MarkerPilot::current() ?? [
            'url' => (string) $url,
            'key' => (string) config('marker.pilot_key'),
            'tournament' => config('marker.pilot_tournament_id'),
        ];
    }

    private function isPilot(array $connection): bool
    {
        $host = parse_url($connection['url'], PHP_URL_HOST);

        return filled($connection['key']) || filled($connection['tournament'])
            || str_ends_with(strtolower((string) $host), '.trycloudflare.com');
    }

    public function request(string $method, string $path, array $payload = [], ?string $token = null): array
    {
        $connection = $this->connection();
        if (! $this->validConnection($connection)) {
            throw new RuntimeException('Configurá la dirección del servidor de marcadores.');
        }

        return $this->send($connection['url'], $connection['key'], $method, $path, $payload, $token);
    }

    public function verifyPilot(string $url, string $key): bool
    {
        return ($this->send($url, $key, 'GET', '/health', [], null)['ok'] ?? false) === true;
    }

    private function send(string $baseUrl, string $key, string $method, string $path, array $payload, ?string $token): array
    {
        try {
            $client = Http::acceptJson()->asJson()->withoutRedirecting()->connectTimeout(2)->timeout(4);
            if ($key !== '') {
                $client = $client->withHeaders(['X-Marker-Pilot-Key' => $key]);
            }
            if ($token !== null) {
                $client = $client->withToken($token);
            }
            $url = rtrim($baseUrl, '/').'/api/v1/fab'.$path;
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
