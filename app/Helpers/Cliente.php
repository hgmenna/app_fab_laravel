<?php

namespace App\Helpers;

final class Cliente
{
    public static function money(float|int|string|null $amount): string
    {
        return config('cliente.simbolo_moneda').' '.number_format(
            (float) $amount,
            (int) config('cliente.decimales', 2),
            (string) config('cliente.separador_decimal', ','),
            (string) config('cliente.separador_miles', '.'),
        );
    }
}
