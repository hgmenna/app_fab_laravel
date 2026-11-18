<?php

use App\Http\Controllers\FiveQuillasRankingController;
use App\Http\Controllers\FiveQuillasRankingPdfController;
use App\Http\Controllers\ThreeCushionRankingController;
use App\Http\Controllers\TournamentRegistrationPdfController;
use App\Http\Controllers\UpcomingTournamentsController;
use App\Http\Controllers\BankTransferOrderPdfController;
use App\Http\Controllers\MembershipAccountPdfController;
use App\Http\Controllers\MembershipPaymentPdfController;
use App\Http\Controllers\MembershipEntityStatementPdfController;
use App\Http\Controllers\FiveQuillasCompetitionPublicController;
use App\Http\Controllers\FiveQuillasCompetitionPageController;
use App\Http\Controllers\FiveQuillasCompetitionBroadcastController;
use App\Http\Controllers\FiveQuillasCompetitionSectionPdfController;
use Illuminate\Support\Facades\Route;

Route::get('/torneos-proximos.json', UpcomingTournamentsController::class);
Route::get('/reports/player-performance/{token}', \App\Http\Controllers\PlayerPerformancePdfDownloadController::class)
    ->whereUuid('token')->name('players.performance-pdf');
Route::get('/torneos.json', UpcomingTournamentsController::class)
    ->name('tournaments.public-calendar');
Route::get('/ranking-carambola-3-bandas.json', ThreeCushionRankingController::class);
Route::get('/ranking-5-quillas.json', FiveQuillasRankingController::class);
Route::get('/ranking-5-quillas.pdf', FiveQuillasRankingPdfController::class);
Route::get('/torneos/5-quillas/{slug}/competencia.json', FiveQuillasCompetitionPublicController::class)
    ->name('five-quillas-competition.public');
Route::get('/torneos/5-quillas/{slug}/transmision', FiveQuillasCompetitionBroadcastController::class)
    ->name('five-quillas-competition.broadcast');
Route::get('/torneos/5-quillas/{slug}', FiveQuillasCompetitionPageController::class)
    ->name('five-quillas-competition.page');
Route::get('/torneos/5-quillas/{slug}/secciones/{section}/pdf', FiveQuillasCompetitionSectionPdfController::class)
    ->name('five-quillas-competition.section-pdf');
Route::get('/ordenes-transferencia/{order}/pdf', BankTransferOrderPdfController::class)
    ->middleware('signed')
    ->name('bank-transfer-orders.pdf');
Route::get('/inscripciones/{registration}/constancia-pdf', TournamentRegistrationPdfController::class)
    ->middleware('signed')
    ->name('tournament-registrations.pdf');
Route::get('/membresias/cuentas/{account}/pdf', MembershipAccountPdfController::class)
    ->middleware('signed')->name('membership-accounts.pdf');
Route::get('/membresias/movimientos/{payment}/pdf', MembershipPaymentPdfController::class)
    ->middleware('signed')->name('membership-payments.pdf');
Route::get('/membresias/estado-entidad/pdf', MembershipEntityStatementPdfController::class)
    ->middleware('signed')->name('membership-entity-statements.pdf');

Route::get('/', function () {
    return view('welcome');
});

Route::get('/debug-path', function () {
    return [
        'base_path' => base_path(),
        'public_html_1' => realpath(base_path('../public_html')),
        'public_html_2' => realpath(base_path('../../public_html')),
        'public_html_3' => realpath('/home'),
        'public_html_4' => realpath('/home/usuario'),
        'public_html_5' => realpath('/home/usuario/public_html'),
    ];
});

Route::get('/app', \App\Http\Controllers\MobileAppController::class)
    ->name('mobile-app');

Route::view('/app/instalar', 'mobile-install')->name('mobile-app.install');

Route::get('/app/torneos/{tournament}/inscripcion', [\App\Http\Controllers\MobileRegistrationController::class, 'show'])->name('mobile-registration.show');
Route::get('/app/torneos/{tournament}/inscripcion/opciones', [\App\Http\Controllers\MobileRegistrationController::class, 'options'])->middleware('throttle:60,1')->name('mobile-registration.options');
Route::post('/app/torneos/{tournament}/inscripcion', [\App\Http\Controllers\MobileRegistrationController::class, 'store'])->middleware('throttle:15,1')->name('mobile-registration.store');

Route::get('/app/jugadores', [\App\Http\Controllers\MobilePlayersController::class, 'index'])->name('mobile-players.index');
Route::get('/app/jugadores/{player}/desempeno', [\App\Http\Controllers\MobilePlayersController::class, 'performance'])
    ->whereNumber('player')->name('mobile-players.performance');

Route::get('/app/calendario.json', UpcomingTournamentsController::class)->name('mobile-app.calendar');
Route::get('/app/torneos/{tournament}/organizacion', \App\Http\Controllers\MobileTournamentOrganizationController::class)
    ->name('mobile-tournament.organization');

Route::get('/app/cuenta', [\App\Http\Controllers\MobileAccountController::class, 'show'])->name('mobile-account.show');
Route::post('/app/cuenta', [\App\Http\Controllers\MobileAccountController::class, 'login'])->middleware('throttle:6,1')->name('mobile-account.login');
Route::post('/app/cuenta/salir', [\App\Http\Controllers\MobileAccountController::class, 'logout'])->name('mobile-account.logout');

Route::view('/app/resultados/{slug}', 'mobile-results')->where('slug', '[a-z0-9-]+')->name('mobile-results.show');

Route::get('/fab-mobile/{file}', \App\Http\Controllers\MobileAssetController::class)
    ->where('file', '[a-zA-Z0-9.-]+')->name('mobile-app.asset');
Route::get('/fab-mobile-worker.js', fn () => response()->file(
    base_path('public/fab-mobile-worker.js'),
    ['Content-Type' => 'text/javascript', 'Cache-Control' => 'no-cache'],
));
