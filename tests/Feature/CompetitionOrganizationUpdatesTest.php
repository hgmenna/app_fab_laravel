<?php

use App\Livewire\CompetitionOrganizationUpdates;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;

it('refreshes another operator only when their tournament changes', function () {
    config(['app.key' => 'base64:'.base64_encode(str_repeat('a', 32))]);

    $operator = Livewire::test(CompetitionOrganizationUpdates::class, [
        'tournamentId' => 21,
        'revision' => null,
    ]);

    Cache::put(CompetitionOrganizationUpdates::revisionKey(22), 'other-tournament');
    $operator->call('checkForChanges')->assertNotDispatched('competition-organization-changed');

    Cache::put(CompetitionOrganizationUpdates::revisionKey(21), 'result-saved');
    $operator->call('checkForChanges')
        ->assertDispatched('competition-organization-changed')
        ->assertSet('revision', 'result-saved');
    $operator->call('checkForChanges')->assertNotDispatched('competition-organization-changed');
});
