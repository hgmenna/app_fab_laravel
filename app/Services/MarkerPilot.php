<?php

namespace App\Services;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

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

    public static function activate(int $tournament, string $url, string $key): bool
    {
        return Cache::put(self::CACHE_KEY, [
            'tournament' => $tournament,
            'url' => $url,
            'key' => Crypt::encryptString($key),
        ], now()->addHours(4));
    }

    public static function stop(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
