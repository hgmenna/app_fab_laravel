<?php

use App\Models\Tournament;
use Illuminate\Support\Carbon;

it('keeps a cross month tournament in progress through its last day in Argentina', function ($day, $expected) {
    config(['app.timezone' => 'UTC']);
    $tournament = new Tournament(['start_date' => '2026-10-31', 'end_date' => '2026-11-01']);
    $reference = Carbon::parse($day, 'America/Argentina/Buenos_Aires');
    expect($tournament->calendarStatus($reference))->toBe($expected);
})->with([
    ['2026-10-30 12:00:00', 'Próximo'],
    ['2026-10-31 00:00:00', 'En curso'],
    ['2026-10-31 23:59:59', 'En curso'],
    ['2026-11-01 00:00:00', 'En curso'],
    ['2026-11-01 12:00:00', 'En curso'],
    ['2026-11-01 23:59:59', 'En curso'],
    ['2026-11-02 00:00:00', 'Finalizado'],
]);

it('compares calendar days across years and handles single day tournaments', function ($start, $end, $day, $expected) {
    $tournament = new Tournament(['start_date' => $start, 'end_date' => $end]);
    expect($tournament->calendarStatus(Carbon::parse($day, 'America/Argentina/Buenos_Aires')))->toBe($expected);
})->with([
    ['2026-12-31', '2027-01-01', '2027-01-01', 'En curso'],
    ['2026-12-31', '2027-01-01', '2027-01-02', 'Finalizado'],
    ['2026-11-01', '2026-11-01', '2026-11-01', 'En curso'],
    ['2026-11-01', null, '2026-11-01', 'En curso'],
    ['2026-11-01', null, '2026-11-02', 'Finalizado'],
]);

it('uses the Argentine day by default even before midnight UTC becomes midnight locally', function () {
    Carbon::setTestNow(Carbon::parse('2026-11-02 01:30:00', 'UTC'));
    try {
        expect((new Tournament(['start_date' => '2026-10-31', 'end_date' => '2026-11-01']))->calendarStatus())->toBe('En curso');
    } finally {
        Carbon::setTestNow();
    }
});
