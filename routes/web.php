<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::prefix('web')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('/login', [App\Http\Controllers\Api\Client\Web\Auth\LoginController::class, 'login'])->name('login');
        Route::post('/register', [App\Http\Controllers\Api\Client\Web\Auth\LoginController::class, 'register']);
        Route::post('/logout', [App\Http\Controllers\Api\Client\Web\Auth\LoginController::class, 'logout']);

    });
    Route::group(['prefix' => 'dashboard', 'middleware' => ['auth']], function () {
        Route::get('/', [App\Http\Controllers\Api\Client\Web\Dashboard\DashboardController::class, 'dashboard']);
    });
    Route::prefix('license')->group(function () {
        Route::post('/get', [App\Http\Controllers\Api\Client\Web\License\LicenseController::class, 'get']);
    });
});