<?php

namespace App\Console\Commands;

use DateTimeZone;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class VerifyClientConfiguration extends Command
{
    protected $signature = 'cliente:verificar {--database : Verificar también la conexión a la base de datos}';

    protected $description = 'Verifica la identidad y los archivos de esta instalación sin modificar datos ni mostrar credenciales';

    public function handle(): int
    {
        $errors = [];
        foreach (['codigo', 'nombre', 'nombre_legal', 'sigla', 'sistema', 'pais_nombre'] as $key) {
            if (trim((string) config("cliente.{$key}")) === '') {
                $errors[] = "Falta cliente.{$key}.";
            }
        }
        foreach (['web_url', 'sistema_url'] as $key) {
            $url = (string) config("cliente.{$key}");
            if (! filter_var($url, FILTER_VALIDATE_URL) || ! in_array(parse_url($url, PHP_URL_SCHEME), ['https', 'http'], true)) {
                $errors[] = "La dirección cliente.{$key} no es válida.";
            }
        }
        if (! in_array(config('cliente.zona_horaria'), DateTimeZone::listIdentifiers(DateTimeZone::ALL_WITH_BC), true)) {
            $errors[] = 'La zona horaria del cliente no es válida.';
        }
        if (! preg_match('/^[A-Z]{2}$/', (string) config('cliente.pais'))) {
            $errors[] = 'El país debe tener un código de dos letras mayúsculas.';
        }
        if (! preg_match('/^[A-Z]{3}$/', (string) config('cliente.moneda'))) {
            $errors[] = 'La moneda debe tener un código de tres letras mayúsculas.';
        }
        if (! preg_match('/^#[0-9a-fA-F]{6}$/', (string) config('cliente.color_marca'))) {
            $errors[] = 'El color de marca debe tener formato #RRGGBB.';
        }
        if ((int) config('cliente.decimales') < 0 || (int) config('cliente.decimales') > 4) {
            $errors[] = 'La cantidad de decimales debe estar entre 0 y 4.';
        }
        foreach (['email', 'proveedor.email'] as $key) {
            if (filled(config("cliente.{$key}")) && ! filter_var(config("cliente.{$key}"), FILTER_VALIDATE_EMAIL)) {
                $errors[] = "El correo cliente.{$key} no es válido.";
            }
        }
        foreach (['logo', 'pie_documentos', 'favicon'] as $key) {
            $relative = (string) config("cliente.{$key}");
            if ($relative === '' || str_contains($relative, '..') || ! is_file(rtrim((string) config('cliente.raiz_publica'), '/\\').'/'.$relative)) {
                $errors[] = "No se encontró el archivo configurado en cliente.{$key}.";
            }
        }
        if ($this->option('database')) {
            try {
                DB::connection()->getPdo();
            } catch (\Throwable) {
                $errors[] = 'No se pudo conectar con la base de datos. Revisá DB_* sin compartir sus credenciales.';
            }
        }
        foreach ($errors as $error) {
            $this->error($error);
        }
        if ($errors !== []) {
            return self::FAILURE;
        }
        $this->info('Configuración verificada: '.config('cliente.nombre').' ('.config('cliente.codigo').').');
        $this->line('Sistema completo, con base de datos y configuración independientes por instalación.');

        return self::SUCCESS;
    }
}
