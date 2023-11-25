<?php

namespace App\Http\Controllers\Api\Client\Pterodactyl;

use App\Services\LicenseService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\File;
use App\Models\License;
use Illuminate\Support\Facades\Validator;

class McVersionsController extends BaseController
{
    public function getVersions(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required',
            'stype' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'message' => 'You need to provide a stype and a id!'
            ], 400);
        }

        $licenseService = app(LicenseService::class);

        $clientController = new ClientController($licenseService);

        $license = $clientController->checkLicense($request->id , 296 , $request->ip());
        if (gettype($license) === 'array' && isset($license['message']) && $license['message'] === 'done' ) {
            $versions = null;

            $validStypes = [
                'modpacks', 'others'
            ];

            if (!in_array($request->stype, $validStypes)) {
                $maj = ucfirst($request->stype);
                $versions = Http::get("https://cdn.bagou450.com/versions/minecraft/getlist/$maj.json")->json();
            } elseif ($request->stype === 'modpacks') {
                $maj = ucfirst($request->modpacktype);
                $versions = Http::get("https://cdn.bagou450.com/versions/minecraft/getlist/modpacks/$maj.json")->json();
            } elseif ($request->stype === 'others') {
                $versions = Http::get("http://$request->url/versionlist.json")->json();
            } else {
                return response()->json([
                    'message' => 'Stype is invalid.'
                ], 400);
            }

            if ($versions) {
                return response()->json([
                    'message' => 'Good',
                    'data' => $versions
                ], 200);
            } else {
                return response()->json([
                    'message' => 'Unable to fetch versions.'
                ], 400);
            }
        } else {
            return $license;
        }
    }

    public function downloadVersion(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required',
            'stype' => 'required',
            'version' => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json([
                'message' => 'You need to provide a styp, a id and also a version!'
            ], 400);
        }

        $licenseService = app(LicenseService::class);

        $clientController = new ClientController($licenseService);

        $license = $clientController->checkLicense($request->id , 296 , $request->ip());

        if (gettype($license) === 'array' && isset($license['message']) && $license['message'] === 'done' ) {
            $url = '';

            $nozipStypes = [
                'bungeecord', 'velocity', 'waterfall', 'flamecord', 'mohist', 'catserver', 'sponge', 'paper', 'magma',
                'spigot', 'purpur', 'tuinity', 'sponge', 'vanilla', 'snapshot'
            ];
            $zipStypes = [
                'modpacks', 'forge', 'fabric'
            ];
            if (in_array($request->stype, $nozipStypes)) {
                $url = "https://cdn.bagou450.com/versions/minecraft/$request->stype/$request->version";
            } elseif (in_array($request->stype, $zipStypes)) {
                $url = "https://cdn.bagou450.com/versions/minecraft/$request->stype/$request->version.zip";

            } elseif ($request->stype === 'others') {
                $url = "http://$request->url";
            } else {
                return response()->json([
                    'message' => 'Stype is invalid.'
                ], 400);
            }

            if ($url !== '') {
                $url = str_replace(' ', '%20', $url);
                $headers = get_headers($url, true);
                $contentLength = isset($headers['Content-Length']) ? $headers['Content-Length'] : null;
                if(!$contentLength) {
                    if(isset($headers['content-length'])) {
                        $contentLength = $headers['content-length'];
                    }
                }
                return response()->json([
                    'message' => 'Good',
                    'data' => $url,
                    'size' => $contentLength,
                    'version' => floatval(json_decode($license['version']))
                ], 200);
            } else {
                return response()->json([
                    'message' => 'Unable to fetch version download URL.'
                ], 400);
            }
        } else {
            return $license;
        }
    }
}
