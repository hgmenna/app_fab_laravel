<?php

beforeEach(fn() => config(['app.key' => 'base64:'.base64_encode(str_repeat('a', 32))]));

it('serves the installation page without an account or access to the sports database', function () {
    $this->get('/app/instalar')->assertOk()
        ->assertSee('Instalar FAB Movil')
        ->assertSee('iPhone y iPad')
        ->assertSee('Windows y computadoras')
        ->assertSee(route('mobile-app.install'))
        ->assertSee('fab-mobile/manifest.webmanifest', false);
});

it('serves the PWA installation assets through Laravel for separate public directories', function () {
    $this->get('/fab-mobile/manifest.webmanifest')->assertOk()->assertHeader('Content-Type', 'application/manifest+json');
    $this->get('/fab-mobile/install.js')->assertOk()->assertHeader('Content-Type', 'text/javascript; charset=utf-8');
    $this->get('/fab-mobile/live-refresh.js')->assertOk();
    $this->get('/fab-mobile/live-updates.js')->assertOk();
    $this->get('/fab-mobile/install-qr.png')->assertOk()->assertHeader('Content-Type', 'image/png');
    $this->get('/fab-mobile-worker.js')->assertOk()->assertHeader('Cache-Control', 'no-cache, public');
    $this->get('/fab-mobile/private-file.txt')->assertNotFound();
});

it('uses one stable app identity and includes every mobile section in its scope', function () {
    $manifest = json_decode(file_get_contents(public_path('fab-mobile/manifest.webmanifest')), true, flags: JSON_THROW_ON_ERROR);
    expect($manifest['id'])->toBe('/app')->and($manifest['start_url'])->toBe('/app')
        ->and($manifest['scope'])->toBe('/app')->and($manifest['display'])->toBe('standalone')
        ->and($manifest['prefer_related_applications'])->toBeFalse();
    foreach ([192, 512] as $size) {
        $dimensions = getimagesize(public_path('fab-mobile/icon-'.$size.'.png'));
        expect([$dimensions[0], $dimensions[1]])->toBe([$size, $size]);
    }
});

it('encodes the public installation link in the downloadable QR', function () {
    $reader = new \chillerlan\QRCode\QRCode;
    $decoded = $reader->readFromFile(public_path('fab-mobile/install-qr.png'));
    expect($decoded->data)->toBe('https://sistem.federacionargentinadebillar.org/app/instalar');
});
