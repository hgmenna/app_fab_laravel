<?php

use Illuminate\Support\Facades\Route;

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


