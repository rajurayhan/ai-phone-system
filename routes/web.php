<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/test-filtering', function () {
    return view('test-filtering');
});

Route::get('/debug', function () {
    return view('debug');
});

// Serve the SPA for all routes except API routes
Route::get('/{any}', function () {
    return view('app');
})->where('any', '^(?!api).*$');
