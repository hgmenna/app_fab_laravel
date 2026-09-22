<?php

use App\Models\Tournament;
use Carbon\CarbonImmutable;

uses(Tests\TestCase::class);

afterEach(function (): void {
    CarbonImmutable::setTestNow();
});

it('opens registration only when enabled and inside the configured date range', function (): void {
    CarbonImmutable::setTestNow(CarbonImmutable::parse(
        '2026-09-22 12:00:00',
        'America/Argentina/Buenos_Aires',
    ));

    $tournament = new Tournament([
        'registration_enabled' => true,
        'registration_open_at' => '2026-09-20',
        'registration_close_at' => '2026-09-22',
    ]);

    expect($tournament->isRegistrationOpen())->toBeTrue();
});

it('keeps registration closed when the explicit switch is disabled', function (): void {
    CarbonImmutable::setTestNow(CarbonImmutable::parse(
        '2026-09-22 12:00:00',
        'America/Argentina/Buenos_Aires',
    ));

    $tournament = new Tournament([
        'registration_enabled' => false,
        'registration_open_at' => '2026-09-20',
        'registration_close_at' => '2026-09-25',
    ]);

    expect($tournament->isRegistrationOpen())->toBeFalse();
});

it('keeps registration closed outside the configured date range', function (): void {
    CarbonImmutable::setTestNow(CarbonImmutable::parse(
        '2026-09-22 12:00:00',
        'America/Argentina/Buenos_Aires',
    ));

    $tournament = new Tournament([
        'registration_enabled' => true,
        'registration_open_at' => '2026-09-23',
        'registration_close_at' => '2026-09-30',
    ]);

    expect($tournament->isRegistrationOpen())->toBeFalse();
});
