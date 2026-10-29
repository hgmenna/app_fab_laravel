<?php

use App\Http\Controllers\FiveQuillasCompetitionBroadcastController;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    Schema::create('five_quillas_competitions', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('tournament_id')->nullable();
        $table->string('public_slug');
        $table->boolean('is_public');
        $table->json('settings')->nullable();
    });
});

afterEach(fn () => Schema::dropIfExists('five_quillas_competitions'));

it('renders the tournament waiting screen when results or broadcasting are disabled', function ($public, $enabled) {
    DB::table('five_quillas_competitions')->insert([
        'public_slug' => 'torneo-prueba', 'is_public' => $public,
        'settings' => json_encode(['broadcast' => ['enabled' => $enabled]]),
    ]);
    $view = (new FiveQuillasCompetitionBroadcastController)('torneo-prueba');
    expect($view->name())->toBe('public.five-quillas-competition-unavailable')
        ->and($view->getData())->toHaveKey('competition')
        ->and($view->getData())->not->toHaveKey('jsonUrl');
})->with([[false, false], [false, true], [true, false]]);

it('renders the broadcasting screen when publication and broadcasting are enabled', function () {
    DB::table('five_quillas_competitions')->insert([
        'public_slug' => 'torneo-prueba', 'is_public' => true,
        'settings' => json_encode(['broadcast' => ['enabled' => true]]),
    ]);
    $view = (new FiveQuillasCompetitionBroadcastController)('torneo-prueba');
    expect($view->name())->toBe('public.five-quillas-competition-broadcast')
        ->and($view->getData()['broadcastConfig']['enabled'])->toBeTrue();
});

it('keeps missing tournament URLs as not found', function () {
    (new FiveQuillasCompetitionBroadcastController)('inexistente');
})->throws(ModelNotFoundException::class);
