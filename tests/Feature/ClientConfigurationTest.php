<?php

use App\Helpers\Cliente;
use App\Helpers\FabPath;
use App\Notifications\SystemActivityEmailNotification;
use Illuminate\Support\Facades\File;

it('keeps the current federation as the compatible default', function () {
    expect(config('cliente.codigo'))->toBe('fab')
        ->and(config('cliente.nombre'))->toBe('Federación Argentina de Billar')
        ->and(config('cliente.moneda'))->toBe('ARS')
        ->and(config('fab.paths.public_root'))->toBe(config('cliente.raiz_publica'))
        ->and(config('filesystems.disks.public_path.root'))->toBe(config('cliente.raiz_publica'));
});

it('uses the independent client identity in panel branding documents and email', function () {
    config([
        'cliente.nombre' => 'Federación de Prueba Exterior',
        'cliente.sistema' => 'Sistema Deportivo Exterior',
        'cliente.logo' => 'images/exterior.png',
        'cliente.proveedor.nombre' => 'Proveedor de Prueba',
    ]);
    $brand = view('filament.brand')->render();
    $footer = view('filament.dev-footer')->render();
    $document = view('pdf.generic', ['title' => 'Listado', 'date' => '06/11/2026', 'columns' => [], 'rows' => []])->render();
    $mail = (new SystemActivityEmailNotification('Aviso', 'Mensaje'))->toMail((object) ['name' => 'Responsable']);

    expect($brand)->toContain('Federación de Prueba Exterior', 'images/exterior.png')->not->toContain('Federación Argentina')
        ->and($footer)->toContain('Sistema Deportivo Exterior', 'Proveedor de Prueba')->not->toContain('Federación Argentina')
        ->and($document)->toContain('Sistema Deportivo Exterior', 'Proveedor de Prueba')->not->toContain('Federación Argentina')
        ->and($mail->subject)->toBe('Aviso | Federación de Prueba Exterior')
        ->and(implode(' ', $mail->introLines))->toContain('Sistema Deportivo Exterior');
});

it('formats client money without changing sporting values or converting amounts', function () {
    config(['cliente.simbolo_moneda' => 'US$', 'cliente.decimales' => 2, 'cliente.separador_decimal' => '.', 'cliente.separador_miles' => ',']);
    expect(Cliente::money('1234.5'))->toBe('US$ 1,234.50');
    config(['cliente.simbolo_moneda' => '$', 'cliente.decimales' => 0, 'cliente.separador_decimal' => ',', 'cliente.separador_miles' => '.']);
    expect(Cliente::money(1234))->toBe('$ 1.234');
});

it('uses the configured country day without changing the historical tournament dates', function () {
    \Illuminate\Support\Carbon::setTestNow(\Illuminate\Support\Carbon::parse('2026-11-02 01:30:00', 'UTC'));
    try {
        $tournament = new \App\Models\Tournament(['start_date' => '2026-10-31', 'end_date' => '2026-11-01']);
        config(['cliente.zona_horaria' => 'America/Argentina/Buenos_Aires']);
        expect($tournament->calendarStatus())->toBe('En curso');
        config(['cliente.zona_horaria' => 'Pacific/Auckland']);
        expect($tournament->calendarStatus())->toBe('Finalizado')
            ->and($tournament->end_date->toDateString())->toBe('2026-11-01');
    } finally {
        \Illuminate\Support\Carbon::setTestNow();
    }
});

it('provides a valid independent environment template without copied credentials', function () {
    $values = \Dotenv\Dotenv::parse(file_get_contents(base_path('.env.cliente.example')));
    expect($values['CLIENTE_CODIGO'])->toBe('cliente-nuevo')
        ->and($values['APP_URL'])->toBe('https://sistema.federacion.example')
        ->and($values['CLIENTE_SIMBOLO_MONEDA'])->toBe('$U')
        ->and($values)->not->toHaveKeys(['DB_PASSWORD', 'APP_KEY']);
});

it('verifies a second installation without modifying its database or exposing secrets', function () {
    $root = storage_path('framework/testing/client-'.uniqid());
    File::ensureDirectoryExists($root);
    File::put($root.'/logo.png', 'test image');
    try {
        config([
            'cliente.codigo' => 'exterior', 'cliente.nombre' => 'Federación Exterior',
            'cliente.nombre_legal' => 'Federación Exterior', 'cliente.sigla' => 'FE',
            'cliente.sistema' => 'Sistema Exterior', 'cliente.pais' => 'UY', 'cliente.pais_nombre' => 'Uruguay',
            'cliente.web_url' => 'https://federacion.example', 'cliente.sistema_url' => 'https://sistema.federacion.example',
            'cliente.zona_horaria' => 'America/Montevideo', 'cliente.moneda' => 'UYU',
            'cliente.raiz_publica' => $root, 'cliente.logo' => 'logo.png',
            'cliente.pie_documentos' => 'logo.png', 'cliente.favicon' => 'logo.png',
            'fab.paths.public_root' => $root, 'fab.paths.logo' => 'logo.png',
        ]);
        expect(FabPath::logo())->toBe($root.'/logo.png');
        $this->artisan('cliente:verificar')->expectsOutputToContain('Federación Exterior')->assertSuccessful();
        config(['cliente.zona_horaria' => 'Invalida/Zona', 'cliente.logo' => 'missing.png']);
        $this->artisan('cliente:verificar')->expectsOutputToContain('zona horaria')->expectsOutputToContain('cliente.logo')->assertFailed();
    } finally {
        File::deleteDirectory($root);
    }
});
