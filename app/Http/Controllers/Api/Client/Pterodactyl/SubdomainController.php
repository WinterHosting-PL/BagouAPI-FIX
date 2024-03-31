<?php

namespace App\Http\Controllers\Api\Client\Pterodactyl;

use App\Http\Controllers\Controller;
use App\Services\LicenseService;
use App\Services\Subdomain\CloudflareService;
use App\Services\Subdomain\GodaddyService;
use App\Services\Subdomain\NameCheapService;
use App\Services\Subdomain\NameService;
use App\Services\Subdomain\OvhService;
use Illuminate\Http\Request;

class SubdomainController extends Controller
{
    public function getList(Request $request) {
        $licenseService = app(LicenseService::class);

        $clientController = new ClientController($licenseService);

        $license = $clientController->checkLicense($request->id , 326 , $request->ip());

        if ( gettype($license) === 'array' && isset($license['message']) && $license['message'] === 'done' ) {
            return response()->json([
                'status' => 'success',
                'data' => [
                    'cloudflare' => ['title' => 'CloudFlare', 'secret' => false, 'consumer' => false],
                    'daddy' => ['title' => 'GoDaddy', 'secret' => true, 'consumer' => false],
                    'ovh' => ['title' => 'OVH', 'secret' => true, 'consumer' => true],
                    'name' => ['title' => 'Name.com', 'secret' => true, 'consumer' => false],
                    'namecheap' => ['title' => 'NameCheap', 'secret' => true, 'consumer' => false]
                ]
            ]);
        }
        return response()->json(['status' => 'error' , 'message' => 'Invalid License.']);
    }

    public function createRecord(Request $request)
    {
        $licenseService = app(LicenseService::class);

        $clientController = new ClientController($licenseService);

        $license = $clientController->checkLicense($request->id , 326 , $request->ip());

        if ( gettype($license) === 'array' && isset($license['message']) && $license['message'] === 'done' ) {
            if ( $request->type === 'daddy' ) {
                $subdomain = new GodaddyService();
                return $subdomain->create($request);
            } else if ( $request->type === 'ovh' ) {
                $subdomain = new OvhService();
                return $subdomain->create($request);
            } else if ( $request->type === 'cloudflare' ) {
                $subdomain = new CloudflareService();
                return $subdomain->create($request);
            } else if ( $request->type === 'name' ) {
                $subdomain = new NameService();
                return $subdomain->create($request);
            } else if ( $request->type === 'namecheap' ) {
                $subdomain = new NameCheapService();
                return $subdomain->create($request);
            }
            return response()->json(['status' => 'errror' , 'message' => 'Malformed request'] , 500);
        }
        return response()->json(['status' => 'error' , 'message' => 'Invalid License.']);



    }

    public function deleteRecord(Request $request)
    {
        $licenseService = app(LicenseService::class);

        $clientController = new ClientController($licenseService);
        
        $license = $clientController->checkLicense($request->id , 326 , $request->ip());
        if ( gettype($license) === 'array' && isset($license['message']) && $license['message'] === 'done' ) {
            if ( $request->type === 'daddy' ) {
                $subdomain = new GodaddyService();
                return $subdomain->delete($request);
            } else if ( $request->type === 'ovh' ) {
                $subdomain = new OvhService();
                return $subdomain->delete($request);
            } else if ( $request->type === 'cloudflare' ) {
                $subdomain = new CloudflareService();
                return $subdomain->delete($request);
            } else if ( $request->type === 'name' ) {
                $subdomain = new NameService();
                return $subdomain->delete($request);
            } else if ( $request->type === 'namecheap' ) {
                $subdomain = new NameCheapService();
                return $subdomain->delete($request);
            }
            return response()->json(['status' => 'errror' , 'message' => 'Malformed request'] , 500);
        }
        return response()->json(['status' => 'error' , 'message' => 'Invalid License.']);
    }


}
