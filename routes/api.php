<?php

use App\Http\Controllers\ClientController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
Route::prefix('client')->group(function () {
    Route::prefix('web')->group(function () {
        Route::prefix('license')->group(function () {
            Route::post('/get', [App\Http\Controllers\Api\Client\Web\License\LicenseController::class, 'get']);
        });
    });
    Route::prefix('pterodactyl')->group(function () {

    Route::get('/getautoinstaller', [App\Http\Controllers\Api\Client\Pterodactyl\ClientController::class, 'getAutoInstaller']);
    Route::get('/checkOnline', [App\Http\Controllers\Api\Client\Pterodactyl\ClientController::class, 'checkOnline']);
    Route::get('/addonsList', [App\Http\Controllers\Api\Client\Pterodactyl\ClientController::class, 'addonsList']);
    Route::get('/getVersion', [App\Http\Controllers\Api\Client\Pterodactyl\ClientController::class, 'getVersion']);
    
    Route::get('/checkIfExist', [App\Http\Controllers\Api\Client\Pterodactyl\ClientController::class, 'checkIfExist']);
    //Cloud Servers
    Route::get('/checklicense', [App\Http\Controllers\Api\Client\Pterodactyl\ClientController::class, 'checklicense']);


    Route::post('/license', [App\Http\Controllers\Api\Client\Pterodactyl\ClientController::class, 'getLicense']);

    Route::delete('/license', [App\Http\Controllers\Api\Client\Pterodactyl\ClientController::class, 'deleteLicense']);
    
    Route::prefix('plugins')->group(function () {
        Route::get('/bukkit', [App\Http\Controllers\Api\Client\Pterodactyl\PluginsController::class, 'getBukkit']);
        Route::get('/spigot', [App\Http\Controllers\Api\Client\Pterodactyl\PluginsController::class, 'getSpigot']);
        Route::get('/polymart', [App\Http\Controllers\Api\Client\Pterodactyl\PluginsController::class, 'getPolymart']);
        Route::get('/custom', [App\Http\Controllers\Api\Client\Pterodactyl\PluginsController::class, 'getCustom']);
        Route::get('/getVersions', [App\Http\Controllers\Api\Client\Pterodactyl\PluginsController::class, 'getVersions']);
        Route::get('/getMcVersions', [App\Http\Controllers\Api\Client\Pterodactyl\PluginsController::class, 'getMcVersions']);
        Route::get('/getCategories', [App\Http\Controllers\Api\Client\Pterodactyl\PluginsController::class, 'getCategories']);
        Route::get('/download', [App\Http\Controllers\Api\Client\Pterodactyl\PluginsController::class, 'Download']);

        
    });
    Route::prefix('mcversions')->group(function () {
        Route::get('/', [App\Http\Controllers\Api\Client\Pterodactyl\McVersionsController::class, 'getVersions']);
        Route::get('/download', [App\Http\Controllers\Api\Client\Pterodactyl\McVersionsController::class, 'downloadVersion']);
    });
    Route::prefix('mods')->group(function () {
        Route::get('/', [App\Http\Controllers\Api\Client\Pterodactyl\McModsController::class, 'getMcMods']);
        Route::get('/versions', [App\Http\Controllers\Api\Client\Pterodactyl\McModsController::class, 'getMcModsVersions']);
        Route::get('/description',[App\Http\Controllers\Api\Client\Pterodactyl\McModsController::class, 'getMcModsDescription']);
        Route::get('/getMcVersions',[App\Http\Controllers\Api\Client\Pterodactyl\McModsController::class, 'getMcVersions']);
        
    });
    
});
});