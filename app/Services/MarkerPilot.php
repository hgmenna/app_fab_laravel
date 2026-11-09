<?php

namespace App\Services;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use RuntimeException;

final class MarkerPilot
{
    private const CACHE_KEY = 'marker:temporary-pilot';

    /** @return array{url: string, key: string, tournament: int}|null */
    public static function current(): ?array
    {
        $pilot = Cache::get(self::CACHE_KEY);
        if ($pilot === null) {
            return null;
        }

        try {
            $pilot['key'] = Crypt::decryptString($pilot['key']);
        } catch (DecryptException) {
            return null;
        }

        return $pilot;
    }

    public static function enable(int $tournament, string $url, string $key, MarkerApi $api): void
    {
        $url = rtrim(trim($url), '/');
        $key = trim($key);
        if (! preg_match('#^https://[a-z0-9-]+\.trycloudflare\.com$#D', $url)) {
            throw new RuntimeException('Ingresá la dirección HTTPS temporal de Cloudflare.');
        }
        if (! preg_match('/^[a-f0-9]{64}$/D', $key)) {
            throw new RuntimeException('La clave temporal debe tener 64 caracteres hexadecimales.');
        }
        if (! app()->environment('testing') && in_array(config('cache.default'), ['array', 'null'], true)) {
            throw new RuntimeException('La prueba necesita una caché persistente.');
        }
        if (! $api->verifyPilot($url, $key)) {
            throw new RuntimeException('No se pudo verificar la conexión de prueba.');
        }
        $saved = Cache::put(self::CACHE_KEY, [
            'tournament' => $tournament,
            'url' => $url,
            'key' => Crypt::encryptString($key),
        ], now()->addHours(4));
        if (! $saved) {
            throw new RuntimeException('No se pudo guardar la conexión de prueba.');
        }
    }

    public static function stop(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
