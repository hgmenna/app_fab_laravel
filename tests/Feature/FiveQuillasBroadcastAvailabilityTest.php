<?php

use App\Helpers\CompetitionViewVersion;
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
    $response = (new FiveQuillasCompetitionBroadcastController)('torneo-prueba');
    $view = $response->getOriginalContent();
    expect($view->name())->toBe('public.five-quillas-competition-unavailable')
        ->and($view->getData())->toHaveKey('competition')
        ->and($view->getData())->not->toHaveKey('jsonUrl')
        ->and($response->headers->hasCacheControlDirective('no-store'))->toBeTrue();
})->with([[false, false], [false, true], [true, false]]);

it('renders the broadcasting screen when publication and broadcasting are enabled', function () {
    DB::table('five_quillas_competitions')->insert([
        'public_slug' => 'torneo-prueba', 'is_public' => true,
        'settings' => json_encode(['broadcast' => ['enabled' => true]]),
    ]);
    $response = (new FiveQuillasCompetitionBroadcastController)('torneo-prueba');
    $view = $response->getOriginalContent();
    expect($view->name())->toBe('public.five-quillas-competition-broadcast')
        ->and($view->getData()['broadcastConfig']['enabled'])->toBeTrue()
        ->and($view->getData()['viewVersion'])->toBe(CompetitionViewVersion::current())
        ->and($response->headers->hasCacheControlDirective('no-store'))->toBeTrue();
});

it('keeps missing tournament URLs as not found', function () {
    (new FiveQuillasCompetitionBroadcastController)('inexistente');
})->throws(ModelNotFoundException::class);
