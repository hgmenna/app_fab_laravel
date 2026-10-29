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

it('matches the publication organization name to the configured logo', function ($official, $source, $clubSource, $expected) {
    expect(publicationTournament($official, $source, $clubSource)->publicationOrganizationName())->toBe($expected);
})->with([
    [false, 'none', 'venue_federation', 'Federación Provincial'],
    [false, 'none', 'venue_club', 'Club Organizador'],
    [true, 'venue_federation', 'venue_federation', 'Federación Provincial'],
    [true, 'national_federation', 'venue_federation', 'Federación Argentina'],
    [true, 'none', 'venue_federation', 'Organización del torneo'],
]);

it('uses the provincial name when a selected club has no logo', function () {
    $tournament = publicationTournament(false, 'none', 'venue_club');
    $tournament->venue->logo_path = null;
    expect($tournament->publicationLogoPath())->toBe('logos/provincial.png')
        ->and($tournament->publicationOrganizationName())->toBe('Federación Provincial');
});
