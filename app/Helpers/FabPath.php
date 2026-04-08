<?php

namespace App\Helpers;

class FabPath
{
    public static function root(): string
    {
        return config('fab.paths.public_root');
    }

    public static function absolute(string $relative): string
    {
        return self::root() . '/' . ltrim($relative, '/');
    }

    public static function logo(): string
    {
        return self::absolute(config('fab.paths.logo'));
    }

    public static function footer(): string
    {
        return self::absolute(config('fab.paths.footer'));
    }

    public static function pagos(string $file = ''): string
    {
        $base = self::absolute(config('fab.paths.pagos'));
        return $file ? "{$base}/{$file}" : $base;
    }

    public static function inscripciones(string $file = ''): string
    {
        $base = self::absolute(config('fab.paths.inscripciones'));
        return $file ? "{$base}/{$file}" : $base;
    }
}

