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
use Illuminate\Support\Facades\Route;

Route::get('/torneos-proximos.json', UpcomingTournamentsController::class);
Route::get('/ranking-carambola-3-bandas.json', ThreeCushionRankingController::class);
Route::get('/ranking-5-quillas.json', FiveQuillasRankingController::class);
Route::get('/ranking-5-quillas.pdf', FiveQuillasRankingPdfController::class);
Route::get('/torneos/5-quillas/{slug}/competencia.json', FiveQuillasCompetitionPublicController::class)
    ->name('five-quillas-competition.public');
Route::get('/torneos/5-quillas/{slug}', FiveQuillasCompetitionPageController::class)
    ->name('five-quillas-competition.page');
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
