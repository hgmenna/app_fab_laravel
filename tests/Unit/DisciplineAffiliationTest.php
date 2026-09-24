<?php

use App\Models\City;
use App\Models\Club;
use App\Models\Discipline;
use App\Models\Federation;
use App\Models\State;

it('uses the configured national federation for a direct affiliation discipline', function () {
    $nationalFederation = new Federation([
        'name' => 'Federación Argentina de Billar',
        'short_name' => 'FAB',
    ]);

    $discipline = new Discipline([
        'name' => 'Disciplina directa',
        'affiliation_mode' => 'direct',
    ]);
    $discipline->setRelation('directFederation', $nationalFederation);

    expect($discipline->federationForClub(null))->toBe($nationalFederation);

});

it('uses the club provincial federation for a provincial affiliation discipline', function () {
    $provincialFederation = new Federation([
        'name' => 'Federación Provincial',
        'short_name' => 'FP',
    ]);

    $state = new State;
    $state->setRelation('federation', $provincialFederation);

    $city = new City;
    $city->setRelation('state', $state);

    $club = new Club;
    $club->setRelation('city', $city);

    $discipline = new Discipline([
        'name' => 'Disciplina provincial',
        'affiliation_mode' => 'provincial',
    ]);

    expect($discipline->federationForClub($club))->toBe($provincialFederation);
});
