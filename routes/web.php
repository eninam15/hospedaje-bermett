<?php

use Illuminate\Support\Facades\Route;

// Todas las rutas van a la SPA de Vue
Route::get('/{any}', function () {
    return view('app');
})->where('any', '.*');