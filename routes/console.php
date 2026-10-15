<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('ranking:apply-category-promotions')
    ->dailyAt('00:05')
    ->timezone('America/Argentina/Buenos_Aires')
    ->withoutOverlapping();

Schedule::command('player-categories:apply-due-changes')
    ->dailyAt('00:10')
    ->timezone('America/Argentina/Buenos_Aires')
    ->withoutOverlapping();

Schedule::command('memberships:expire-overdue')
    ->dailyAt('00:15')
    ->timezone('America/Argentina/Buenos_Aires')
    ->withoutOverlapping();

Schedule::command('bank-transfers:expire')
    ->dailyAt('00:20')
    ->timezone('America/Argentina/Buenos_Aires')
    ->withoutOverlapping();
