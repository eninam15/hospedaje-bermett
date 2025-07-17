<?php

use Illuminate\Support\Facades\Route;

// Rutas específicas primero (si las tienes)
Route::get('/', function () {
    return view('app');
});

// Excluir rutas de API del wildcard
Route::get('/{any}', function () {
    return view('app');
})->where('any', '^(?!api).*$');

// Alternativa más explícita (recomendada):
// Route::fallback(function () {
//     return view('app');
// });