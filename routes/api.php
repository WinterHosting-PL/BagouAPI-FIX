<?php

use App\Http\Controllers\Api\Client\Web\Admin\Blog\AdminBlogController;
use App\Http\Controllers\Api\Client\Web\Admin\Blog\AdminCategoryController;
use App\Http\Controllers\Api\Client\Web\Admin\Licenses\LicensesController;
use App\Http\Controllers\Api\Client\Web\Admin\Users\AdminUsersController;
use App\Http\Controllers\Api\Client\Web\Auth\PasskeysController;
use App\Http\Controllers\Api\Client\Web\Blog\BlogController;
use App\Http\Controllers\Api\Client\Web\Blog\CategoryController;
use App\Http\Controllers\Api\Client\Web\License\LicenseController;
use App\Http\Controllers\ClientController;
use Illuminate\Http\Request;
use Illuminate\Support\FacadesRoute;
use App\Http\Controllers\Api\Client\Web\Shop\Orders\OrdersController;
use App\Http\Controllers\Api\Client\Web\Admin\Products\ProductsController;
use App\Http\Controllers\Api\Client\Web\Account\TicketController;
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
        Route::post('/sendContact', [App\Http\Controllers\Api\Client\Web\ClientController::class, 'sendContact']);
        Route::prefix('addons')->group(function () {
            Route::get('/get', [App\Http\Controllers\Api\Client\Web\AddonsController::class, 'get']);
            Route::get('/getone', [App\Http\Controllers\Api\Client\Web\AddonsController::class, 'getone']);

        });
        // Routes pour les blogs
        Route::get('blogs', [BlogController::class, 'index']);
        Route::get('blogs/{blog}', [BlogController::class, 'get']);

        // Routes pour les catégories
        Route::get('categories', [CategoryController::class, 'index']);
        Route::get('categories/{category}', [CategoryController::class, 'get']);

        Route::prefix('admin')->group(function () {
            // Routes pour les blogs
            Route::prefix('blogs')->group(function () {
                Route::post('/', [AdminBlogController::class, 'create']);
                Route::post('/{blog}', [AdminBlogController::class, 'edit']);
                Route::delete('/{blog}', [AdminBlogController::class, 'remove']);
            });

            // Routes pour les catégories
            Route::prefix('categories')->group(function () {
                Route::post('/', [AdminCategoryController::class, 'create']);
                Route::put('/{category}', [AdminCategoryController::class, 'edit']);
                Route::delete('/{category}', [AdminCategoryController::class, 'remove']);
            });
            Route::prefix('products')->group(function () {
                Route::get('/', [ProductsController::class, 'listProducts']);
                Route::post('/create', [ProductsController::class, 'createProduct']);
                Route::post('/{id}', [ProductsController::class, 'updateProduct']);
                Route::get('/sync', [ProductsController::class, 'syncProducts']);
            });
            Route::prefix('licenses')->group(function () {
                Route::get('/', [LicensesController::class, 'listLicenses']);
                Route::post('/reset/{license}', [LicensesController::class, 'resetLicense']);
                Route::post('/blacklist/{license}', [LicensesController::class, 'licenseBlacklist']);
            });
               Route::prefix('users')->group(function () {
                   Route::get('/', [AdminUsersController::class, 'get']);
                   Route::post('/{id}', [AdminUsersController::class, 'edit']);
               });
        });

        Route::prefix('tickets')->group(function () {
                Route::post('/', [TicketController::class, 'createTicket']);
                Route::post('/{id}/status', [TicketController::class, 'updateTicketStatus']);
                Route::post('/{id}/messages', [TicketController::class, 'addMessage']);
                Route::get('/{id}/messages', [TicketController::class, 'getMessages']);
                Route::get('/', [TicketController::class, 'getTicketList']);
                Route::get('/{id}/details', [TicketController::class, 'getTicketDetails']);
                Route::get('/{attachmentId}/download', [TicketController::class, 'downloadAttachment']);
                Route::get('/getLasted', [TicketController::class, 'getLastedTicketNumber']);
        });
        Route::prefix('auth')->group(function () {
            Route::post('/login', [App\Http\Controllers\Api\Client\Web\Auth\LoginController::class, 'login'])->name('login');
            Route::post('/register', [App\Http\Controllers\Api\Client\Web\Auth\LoginController::class, 'register']);
            Route::post('/tokenlogin', [App\Http\Controllers\Api\Client\Web\Auth\LoginController::class, 'tokenlogin']);
            Route::get('/tokendata', [App\Http\Controllers\Api\Client\Web\Auth\LoginController::class, 'tokendata']);
            Route::get('/isAccount', [App\Http\Controllers\Api\Client\Web\Auth\LoginController::class, 'isAccount']);

            Route::post('/logout', [App\Http\Controllers\Api\Client\Web\Auth\LoginController::class, 'logout'])->middleware('auth:sanctum');
            Route::post('/verify', [App\Http\Controllers\Api\Client\Web\Auth\LoginController::class, 'sendverificationemail'])->middleware('auth:sanctum');
            Route::get('/isLogged', [App\Http\Controllers\Api\Client\Web\Auth\LoginController::class, 'isLogged']);

            //Passkeys
            Route::prefix('passkeys')->group(function () {
                Route::get('/options', [PasskeysController::class, 'createOptions']);
                Route::post('/add', [PasskeysController::class, 'addPasskey']);
                Route::post('/use', [PasskeysController::class, 'validateKey']);
                Route::post('/verification', [PasskeysController::class, 'sendVerificationForAddPassKey']);

            });

            //Oauth
            Route::post('/oauthlogin', [App\Http\Controllers\Api\Client\Web\Auth\LoginController::class, 'oauthlogin']);
                 Route::get('/oauthloginCallback', [App\Http\Controllers\Api\Client\Web\Auth\LoginController::class, 'oauthloginCallback']);

            /* Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
                 return 'test';

                   });*/
            
            Route::get('/sendverificationmail', [App\Http\Controllers\Api\Client\Web\Auth\LoginController::class, 'verifyemail'])->middleware('auth:sanctum');
    
        });
        Route::prefix('account')->group(function () {
            Route::get('/getinfos', [App\Http\Controllers\Api\Client\Web\Account\AccountController::class, 'getinfos'])->middleware('auth:sanctum');
            Route::get('/discord/login', [App\Http\Controllers\Api\Client\Web\Account\AccountController::class, 'discordLogin']);
            Route::get('/discord/callback', [App\Http\Controllers\Api\Client\Web\Account\AccountController::class, 'discordCallback']);
            Route::get('/discord/get', [App\Http\Controllers\Api\Client\Web\Account\AccountController::class, 'getDiscordUser']);


            Route::post('/edit', [App\Http\Controllers\Api\Client\Web\Account\AccountController::class, 'edit'])->middleware('auth:sanctum');
            Route::post('/editinfos', [App\Http\Controllers\Api\Client\Web\Account\AccountController::class, 'editinfos'])->middleware('auth:sanctum');
            //Oauth

            Route::post('/oauth', [App\Http\Controllers\Api\Client\Web\Account\AccountController::class, 'oauthlogin']);
            Route::delete('/oauth', [App\Http\Controllers\Api\Client\Web\Account\AccountController::class, 'deleteOauthLogin']);
            Route::get('/oauthCallback', [App\Http\Controllers\Api\Client\Web\Account\AccountController::class, 'oauthloginCallback']);

        });
        Route::group(['prefix' => 'orders'], function () {
            Route::get('/', [OrdersController::class, 'get']);
            Route::post('/', [OrdersController::class, 'create']);
            Route::get('/status', [OrdersController::class, 'status']);
            Route::get('/invoice', [OrdersController::class, 'generateInvoice']);
            Route::get('/details', [OrdersController::class, 'orderDetails']);
            Route::get('/downloadlink/{order}', [OrdersController::class, 'getDownloadlink'])->name('orders.downloadlink');
            Route::get('/downloads/{token}', [OrdersController::class, 'download'])->name('orders.download');

            Route::get('/downloadOnelink/{id}', [OrdersController::class, 'getDownloadOnelink'])->name('orders.downloadOnelink');
            Route::get('/downloadOne/{token}', [OrdersController::class, 'downloadOne'])->name('orders.downloadOne');

            Route::get('/downloadInvoiceLink/{order}', [OrdersController::class, 'downloadInvoiceLink'])->name('orders.downloadInvoicelink');
            Route::get('/downloadInvoice/{order}', [OrdersController::class, 'downloadInvoice'])->name('orders.downloadInvoice');
        });
        Route::group(['prefix' => 'shop', 'middleware' => 'auth'], function () {
            Route::post('/create', [App\Http\Controllers\Api\Client\Web\Shop\Orders\OrdersController::class, 'create']);
            Route::get('/status', [App\Http\Controllers\Api\Client\Web\Shop\Orders\OrdersController::class, 'updatestatus']);
    
        });
        //License management
        Route::prefix('license')->group(function () {
            //Return List of user license 
            Route::get('/', [App\Http\Controllers\Api\Client\Web\License\LicenseController::class, 'get'])->middleware('auth:sanctum');
            //Send a email with license to the user
            Route::post('/', [App\Http\Controllers\Api\Client\Web\License\LicenseController::class, 'sendLicense'])->middleware('auth:sanctum');
            //Reset license
            Route::post('/reset/{id}', [App\Http\Controllers\Api\Client\Web\License\LicenseController::class, 'resetLicense'])->middleware('auth:sanctum');
            //Link a license to a account
            Route::post('/link/{id}', [App\Http\Controllers\Api\Client\Web\License\LicenseController::class, 'licenseLink'])->middleware('auth:sanctum');
            //Delete a license usage
            Route::delete('/{license}', [App\Http\Controllers\Api\Client\Web\License\LicenseController::class, 'deleteIp'])->middleware('auth:sanctum');
            Route::get('/encryptAllIPs', [App\Http\Controllers\Api\Client\Web\License\LicenseController::class, 'encryptAllIPs'])->middleware('auth:sanctum');

        });
    });
    
    Route::prefix('pterodactyl')->group(function () {

    Route::get('/getautoinstaller', [App\Http\Controllers\Api\Client\Pterodactyl\ClientController::class, 'getAutoInstaller']);
    Route::get('/checkOnline', [App\Http\Controllers\Api\Client\Pterodactyl\ClientController::class, 'checkOnline']);
    Route::get('/addonsList', [App\Http\Controllers\Api\Client\Pterodactyl\ClientController::class, 'addonsList']);
    Route::get('/getVersion', [App\Http\Controllers\Api\Client\Pterodactyl\ClientController::class, 'getVersion']);
    
    Route::get('/checkIfExist', [App\Http\Controllers\Api\Client\Pterodactyl\ClientController::class, 'checkIfExist']);
    Route::get('/checklicense', [App\Http\Controllers\Api\Client\Pterodactyl\ClientController::class, 'checkLicenseCloud']);

    Route::post('/license', [App\Http\Controllers\Api\Client\Pterodactyl\ClientController::class, 'getLicense']);


    
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
    Route::prefix('modpacks')->group(function () {
        Route::get('/', [App\Http\Controllers\Api\Client\Pterodactyl\McModPacksController::class, 'getMcModPacks']);
        Route::get('/versions', [App\Http\Controllers\Api\Client\Pterodactyl\McModPacksController::class, 'getMcModPacksVersions']);
        Route::get('/description',[App\Http\Controllers\Api\Client\Pterodactyl\McModPacksController::class, 'getMcModPacksDescription']);
        Route::get('/getMcVersions',[App\Http\Controllers\Api\Client\Pterodactyl\McModPacksController::class, 'getMcVersions']);
        Route::get('/getMcModpacksDescription',[App\Http\Controllers\Api\Client\Pterodactyl\McModPacksController::class, 'getMcModpacksDescription']);
        Route::get('/download',[App\Http\Controllers\Api\Client\Pterodactyl\McModPacksController::class, 'download']);
        Route::get('/getEgg',[App\Http\Controllers\Api\Client\Pterodactyl\McModPacksController::class, 'getEgg']);
        Route::get('/getForge',[App\Http\Controllers\Api\Client\Pterodactyl\McModPacksController::class, 'forgeDownload']);
        Route::get('/getFabric',[App\Http\Controllers\Api\Client\Pterodactyl\McModPacksController::class, 'fabricDownload']);

        
    });
});
});