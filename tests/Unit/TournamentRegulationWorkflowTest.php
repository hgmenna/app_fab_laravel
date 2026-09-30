<?php

use App\Models\Tournament;
use App\Models\TournamentRegulationAudit;
use App\Services\TournamentRegulationWorkflow;

uses(Tests\TestCase::class);

function approvedTournamentForUpdate(): Tournament
{
    $tournament = new Tournament([
        'start_date' => '2026-10-10',
        'end_date' => '2026-10-12',
        'venue_id' => 10,
    ]);

    $tournament->setRelation(
        'latestSuccessfulRegulationAudit',
        new TournamentRegulationAudit(['result' => 'approved']),
    );

    return $tournament;
}

it('keeps the successful regulation verification when tournament dates do not change', function () {
    $shouldRevalidate = app(TournamentRegulationWorkflow::class)->shouldRevalidateUpdate([
        'name' => 'Nombre actualizado',
        'start_date' => '2026-10-10',
        'end_date' => '2026-10-12',
    ], approvedTournamentForUpdate());

    expect($shouldRevalidate)->toBeFalse();
});

it('revalidates an approved tournament when its start date changes', function () {
    $shouldRevalidate = app(TournamentRegulationWorkflow::class)->shouldRevalidateUpdate([
        'start_date' => '2026-10-11',
        'end_date' => '2026-10-12',
    ], approvedTournamentForUpdate());

    expect($shouldRevalidate)->toBeTrue();
});

it('revalidates an approved tournament when its end date changes', function () {
    $shouldRevalidate = app(TournamentRegulationWorkflow::class)->shouldRevalidateUpdate([
        'start_date' => '2026-10-10',
        'end_date' => '2026-10-13',
    ], approvedTournamentForUpdate());

    expect($shouldRevalidate)->toBeTrue();
});

it('revalidates an approved tournament when its venue is assigned or changed', function () {
    $shouldRevalidate = app(TournamentRegulationWorkflow::class)->shouldRevalidateUpdate([
        'start_date' => '2026-10-10',
        'end_date' => '2026-10-12',
        'venue_id' => 20,
    ], approvedTournamentForUpdate());

    expect($shouldRevalidate)->toBeTrue();
});

it('validates an update when the tournament has no successful regulation audit', function () {
    $tournament = new Tournament([
        'start_date' => '2026-10-10',
        'end_date' => '2026-10-12',
    ]);
    $tournament->setRelation('latestSuccessfulRegulationAudit', null);

    expect(app(TournamentRegulationWorkflow::class)->shouldRevalidateUpdate([
        'start_date' => '2026-10-10',
        'end_date' => '2026-10-12',
    ], $tournament))->toBeTrue();
});
