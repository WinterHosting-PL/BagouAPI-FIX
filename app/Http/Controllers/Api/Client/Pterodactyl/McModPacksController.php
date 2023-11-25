<?php

namespace App\Http\Controllers\Api\Client\Pterodactyl;

use App\Services\ModpacksService;
use App\Services\ModsVersionsService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use App\Models\License;
use App\Models\ModPacksResult;
use App\Models\ModsVersionsResult;
use App\Models\ModPacksDownloadLinks;
use Config;
use Weidner\Goutte\GoutteFacade;
use Illuminate\Support\Facades\Redirect;
use App\Services\LicenseService;
use Illuminate\Support\Facades\File;
use GuzzleHttp\Client;

class McModPacksController extends BaseController
{
    use AuthorizesRequests , DispatchesJobs , ValidatesRequests;

    private $curseKey;
    private $modPacksService;
    private $modsVersionsService;

    public function __construct(ModPacksService $modPacksService , ModsVersionsService $modsVersionsService)
    {
        $this->curseKey = config('api.cursekey');
        $this->modPacksService = $modPacksService;
        $this->modsVersionsService = $modsVersionsService;
    }

    public function getMcModPacks(Request $request)
    {
        $licenseService = app(LicenseService::class);

        $clientController = new ClientController($licenseService);
        $license = $clientController->checkLicense($request->id , 327 , $request->ip());
        if (gettype($license) === 'array' && isset($license['message']) && $license['message'] === 'done' ) {
            $loaders = $request->loaders ?? false;
            $gameVersions = $request->game_versions ?? false;

           if ($request->search == '' && !$request->game_versions && !$request->loaders) {
                $cached = $this->modPacksService->cachedModpack($request->page , $request->type);
                if ( $cached ) {
                    return $cached;
                }
            }


            $modpacksList = [];
            if(!$request->game_versions) {
                $request->game_versions = '';
            }
            if(!$request->search) {
                $request->search = '';
            }
            if(!$request->loaders) {
                $request->loaders = '';
            }
            switch ($request->type) {
                case  'curseforge':

                    $modpacksList = $this->modPacksService
                        ->getModpacksCurse(
                            $request->game_versions , $request->page , $request->search , $request->loaders);
                    break;
                case 'modrinth':
                    $modpacksList = $this->modPacksService
                        ->getModpacksModrinth(
                            $request->game_versions , $request->page , $request->search , $request->loaders);
                    break;

                case 'ftb':
                    $modpacksList = $this->modPacksService
                        ->getModpacksFtb(
                            $request->game_versions , $request->page , $request->search , $request->loaders);
                    break;

                case 'technicpack':
                    $modpacksList = $this->modPacksService
                        ->getModpacksTechnicpack(
                            $request->game_versions , $request->page , $request->search , $request->loaders);
                    break;

                case 'voidswrath':
                    $modpacksList = $this->modPacksService
                        ->getModpacksVoidsWrath(
                            $request->game_versions , $request->page , $request->search , $request->loaders);
                    break;
                default:
                    return response()->json([
                        'message' => 'Invalid request.'
                    ] , 400);

            }
            if ( !$request->search && !$request->game_versions && !$request->loaders ) {
                $this->modPacksService->addCached($request->type , $request->page , $modpacksList);
            }
            return $modpacksList;
        } else {
            return $license;
        }

    }

public function getMcModPacksVersions(Request $request)
{
    $licenseService = app(LicenseService::class);

    $clientController = new ClientController($licenseService);

    $license = $clientController->checkLicense($request->id , 327 , $request->ip());

    if (gettype($license) === 'array' && isset($license['message']) && $license['message'] === 'done' ) {
        $data = $this->modsVersionsService->getModVersionsFromCache($request->page, $request->modId, $request->type);
        if ($data && $request->search == '') {
            return $data;
        }

        if ($request->type == 'curseforge') {
            $addons = $this->modsVersionsService->getCurseForgeModVersions($request->modId, $request->page);

            if ($request->search == '') {
                $this->modsVersionsService->cacheModVersions($request->page, $request->modId, 'curseforge', $addons);
            }

            return $addons;
        } elseif ($request->type == 'modrinth') {
            $addons = $this->modsVersionsService->getModrinthModVersions($request->modId, $request->page);

            if ($request->search == '') {
                $this->modsVersionsService->cacheModVersions($request->page, $request->modId, 'modrinth', $addons);
            }

            return $addons;
        } else {
            return response()->json([
                'message' => 'Invalid request.'
            ], 400);
        }
    } else {
        return $license;
    }
}


  public function getMcModpacksDescription(Request $request)
{
    $licenseService = app(LicenseService::class);

    $clientController = new ClientController($licenseService);

    $license = $clientController->checkLicense($request->id , 327 , $request->ip());
    if (gettype($license) === 'array' && isset($license['message']) && $license['message'] === 'done' ) {

        if ($request->type == 'curseforge') {
            $description = $this->modPacksService->getCurseForgeModpackDescription($request->modpackId);
        } elseif ($request->type == 'modrinth') {
            $description = $this->modPacksService->getModrinthModpackDescription($request->modpackId);
        } else {
            return response()->json([
                'message' => 'Invalid request.'
            ], 400);
        }

        return $description;
    } else {
        return $license;
    }
}


    public function getMcVersions(Request $request)
    {
        $licenseService = app(LicenseService::class);

        $clientController = new ClientController($licenseService);

        $license = $clientController->checkLicense($request->id , 327 , $request->ip());
        if (gettype($license) === 'array' && isset($license['message']) && $license['message'] === 'done' ) {
            $versions = Http::get('https://launchermeta.mojang.com/mc/game/version_manifest.json')->json();
            $releases = array();
            foreach ($versions['versions'] as $version) {
                if ( $version['type'] === 'release' ) {
                    array_push($releases , $version);
                }
            }
            return $releases;
        } else {
            return $license;
        };
    }

    public function download(Request $request)
    {
        $licenseService = app(LicenseService::class);

        $clientController = new ClientController($licenseService);

        $license = $clientController->checkLicense($request->id , 327 , $request->ip());

        if (gettype($license) === 'array' && isset($license['message']) && $license['message'] === 'done' ) {
            if ($request->type === 'voidswrath') {
                $dataHeaders = get_headers($request->data, true);
                $contentLength = isset($dataHeaders['Content-Length']) ? $dataHeaders['Content-Length'] : null;

                return response()->json([
                    'message' => 'Good',
                    'data' => $request->data,
                    'size' => $contentLength,
                ], 200);
            } elseif ($request->type === 'curseforge') {
                $curseKey = config('api.cursekey');
                $headers = [
                    'User-Agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/92.0.4515.81 Safari/537.36',
                    'x-api-key' => $curseKey,
                ];
                $modpackId = $request->modpackid;

                $versionsList = Http::accept('application/json')->withHeaders($headers)->get("https://api.curseforge.com/v1/mods/$modpackId/files?pageSize=10")->object()->data;

                foreach ($versionsList as $version) {
                    if (isset($version->serverPackFileId)) {
                        $modpackServerFileId = $version->serverPackFileId;
                        $mcVersion = null;
                        $loader = null;

                        foreach ($version->gameVersions as $gameVersion) {
                            if ($gameVersion !== 'Forge' && $gameVersion !== 'Fabric') {
                                $mcVersion = $gameVersion;
                            } else {
                                $loader = $gameVersion;
                            }
                        }

                        $modpackData = Http::accept('application/json')->withHeaders($headers)->get("https://api.curseforge.com/v1/mods/$modpackId/files/$modpackServerFileId")->object()->data->downloadUrl;
                        $effectiveUrl = $this->getEffectiveUrl($modpackData);

                        $dataHeaders = get_headers($effectiveUrl, true);
                        $contentLength = isset($dataHeaders['Content-Length'][1]) ? $dataHeaders['Content-Length'][1] : null;

                        return response()->json([
                            'message' => 'Good',
                            'data' => $effectiveUrl,
                            'mcversion' => $mcVersion,
                            'loader' => $loader,
                            'size' => $contentLength,
                        ], 200);
                    }
                }

                return response()->json([
                    'message' => 'Error',
                    'data' => 'Can\'t found a download URL for this Modpack.',
                ], 500);
            } elseif ($request->type === 'modrinth') {
                return response()->json([
                    'message' => 'Error',
                    'data' => $request->modpackid,
                ], 500);
            }
        }
    }

    private function getEffectiveUrl($url)
    {
        $client = new Client(['allow_redirects' => false]); // Désactiver le suivi automatique des redirections

        $response = $client->head($url, ['http_errors' => false]); // Envoyer une requête HEAD sans récupérer le corps

        if ($response->getStatusCode() === 302 && $response->hasHeader('Location')) {
            $effectiveUrl = $response->getHeader('Location')[0];
            return $effectiveUrl;
        }

        return $url;
    }


    public function getEgg(Request $request)
    {
        $licenseService = app(LicenseService::class);

        $clientController = new ClientController($licenseService);

        $license = $clientController->checkLicense($request->id , 327 , $request->ip());
        if (gettype($license) === 'array' && isset($license['message']) && $license['message'] === 'done' ) {
        $filePath = "../modpacksegg/$request->type.json";

        if (File::exists($filePath)) {
            $content = File::get($filePath);
            return json_decode($content, true);
        } else {
            return response()->json([
                'message' => 'File not found.'
            ], 404);
        }
    } else {
        return $license;
    }
    }

    public function forgeDownload(Request $request)
    {
        $forgeversions = Http::get('https://files.minecraftforge.net/net/minecraftforge/forge/promotions_slim.json')->json();
        $version = str_replace('-Snapshot', '', $request->version);
        if(isset($forgeversions['promos'][$version . '-latest'])) {
            $forgeversion = $forgeversions['promos'][$version . '-latest'];
            return Redirect::to("https://maven.minecraftforge.net/net/minecraftforge/forge/$version-$forgeversion/forge-$version-$forgeversion-installer.jar");
        } else {
            return response()->json([
                'message' => 'Invalid request.'
            ], 400);
        }
    }

    public function fabricDownload(Request $request)
    {

       $fabricVersions = Http::get('https://meta.fabricmc.net/v2/versions/installer')->json();

    $version = collect($fabricVersions)->first(function ($version) {
        return $version['stable'];
    });

    if ($version) {
        $versionNumber = $version['version'];
        return Redirect::to("https://maven.fabricmc.net/net/fabricmc/fabric-installer/$versionNumber/fabric-installer-$versionNumber.jar");
    }

    }
}