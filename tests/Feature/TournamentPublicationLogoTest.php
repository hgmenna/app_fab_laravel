<?php

use App\Models\City;
use App\Models\Club;
use App\Models\Federation;
use App\Models\State;
use App\Models\Tournament;
use App\Models\TournamentType;

function publicationTournament(bool $official, string $source, string $nonOfficialSource = 'venue_federation'): Tournament
{
    $provincialFederation = new Federation(['name' => 'Federación Provincial', 'logo_path' => 'logos/provincial.png']);
    $nationalFederation = new Federation(['name' => 'Federación Argentina', 'logo_path' => 'logos/fab.png']);

    $state = new State;
    $state->setRelation('federation', $provincialFederation);

    $city = new City;
    $city->setRelation('state', $state);

    $club = new Club(['name' => 'Club Organizador', 'logo_path' => 'logos/club.png']);
    $club->setRelation('city', $city);

    $type = new TournamentType([
        'is_official' => $official,
        'publication_logo_source' => $source,
    ]);
    $type->setRelation('publicationFederation', $nationalFederation);

    $tournament = new Tournament(['non_official_logo_source' => $nonOfficialSource]);
    $tournament->setRelation('type', $type);
    $tournament->setRelation('venue', $club);

    return $tournament;
}

it('uses the organizing club federation logo for a non official tournament', function () {
    expect(publicationTournament(false, 'none')->publicationLogoPath())
        ->toBe('logos/provincial.png');
});

it('uses the organizing club logo when selected for a non official tournament', function () {
    expect(publicationTournament(false, 'none', 'venue_club')->publicationLogoPath())
        ->toBe('logos/club.png');
});

it('uses the organizing club provincial federation logo', function () {
    expect(publicationTournament(true, 'venue_federation')->publicationLogoPath())
        ->toBe('logos/provincial.png');
});

it('uses the configured national federation logo', function () {
    expect(publicationTournament(true, 'national_federation')->publicationLogoPath())
        ->toBe('logos/fab.png');
});
