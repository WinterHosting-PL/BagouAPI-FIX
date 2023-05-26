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
  
    Route::get('/csrf-token', function () {
        return csrf_token();
    });
    Route::get('/test-session', function (Illuminate\Http\Request $request) {
        $request->session()->put('key', 'value');
    
        $value = $request->session()->get('key');
    
        return $value;
    })->middleware('web');
    Route::group(['prefix' => 'dashboard', 'middleware' => ['auth']], function () {
        Route::get('/', [App\Http\Controllers\Api\Client\Web\Dashboard\DashboardController::class, 'dashboard']);
    });
 
});