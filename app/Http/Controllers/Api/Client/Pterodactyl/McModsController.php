<?php

namespace App\Http\Controllers\Api\Client\Pterodactyl;

use App\Services\LicenseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\License;
use App\Models\ModsResult;
use App\Models\ModsVersionsResult;
use Illuminate\Routing\Controller as BaseController;

class McModsController extends BaseController
{
    public function getMcMods(Request $request)
    {
        $licenseService = app(LicenseService::class);

        $clientController = new ClientController($licenseService);

        $license = $clientController->checkLicense($request->id , 257 , $request->ip());
        if ($license['message'] !== 'done' ) {
            return $license;
        }

        if (!$request->loaders) {
            $request->loaders = false;
        }

        if (!$request->game_versions) {
            $request->game_versions = false;
        }


       /* if (!$request->search && !$request->game_versions && !$request->loaders) {
            $data = ModsResult::where(['page' => $request->page, 'type' => $request->type])->first();
            if ($data && $data->updated_at->diffInHours(now()) < 24) {
                return $data->result;
            }
        }
*/
        if ($request->type === 'curseforge') {
            $cursekey = config('api.cursekey');
            $headers = [
                'User-Agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/92.0.4515.81 Safari/537.36',
                'x-api-key' => $cursekey
            ];
            $data = [
                'index' => $request->page * 20 - 20,
                'pageSize' => 20,
                'gameId' => 432,
                'classId' => 6,
                'searchFilter' => $request->search,
                'sortField' => 2,
                'sortOrder' => 'desc'
            ];
            if ($request->loaders) {
                $maj = 1;
                if ($request->loaders === 'fabric') {
                    $maj = 4;
                }
                $data['modLoaderType'] = $maj;
            }
            if($request->game_versions) {
                $data['gameVersion'] = $request->game_versions;

            }
                $addons = Http::accept('application/json')
                    ->withHeaders($headers)
                    ->get('https://api.curseforge.com/v1/mods/search', $data)
                    ->object()
                    ->data;
        } else if ($request->type === 'modrinth') {
            $headers = [
                'User-Agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/92.0.4515.81 Safari/537.36'
            ];
            $data = [
                'offset' => $request->page * 20 - 20,
                'limit' => 20,
                'query' => $request->search,
            ];
            if ($request->game_versions && $request->loaders) {
                $data['facets'] = "[[\"categories:$request->loaders\"],[\"versions:$request->game_versions\"],[\"project_type:mod\"]]";
            } else if ($request->game_versions) {
                $data['facets'] = "[[\"versions:$request->game_versions\"],[\"project_type:mod\"]]";

            } else if ($request->loaders) {
                $data['facets'] = "[[\"categories:$request->loaders\"],[\"project_type:mod\"]]";

            } else {
                $data['facets'] = "[[\"project_type:mod\"]]";
            }
            $addons = Http::accept('application/json')
                ->withHeaders($headers)
                ->get('https://api.modrinth.com/v2/search', $data)
                ->object()
                ->hits;
        } else {
            return response()->json([
                'message' => 'Invalid request.'
            ], 400);
        }

        if (!$request->search && !$request->loaders && !$request->game_versions) {
            ModsResult::where('page', '=', $request->page)
                ->where('type', '=', $request->type)
                ->delete();

            ModsResult::create([
                'page' => $request->page,
                'result' => json_encode($addons),
                'type' => $request->type
            ]);
        }

        return $addons;
    }

    public function getMcModsVersions(Request $request)
    {
        $licenseService = app(LicenseService::class);

        $clientController = new ClientController($licenseService);

        $license = $clientController->checkLicense($request->id , 257 , $request->ip());

        if ($license['message'] !== 'done' ) {
            return $license;
        }

        $data = ModsVersionsResult::where([
            'page' => $request->page,
            'modid' => $request->modId,
            'type' => $request->type
        ])->first();

        if ($data && $request->search === '') {
            if ($data->updated_at->diffInHours(now()) < 24) {
                return $data->result;
            }
        }

        if ($request->type === 'curseforge') {
            $cursekey = config('api.cursekey');
            $headers = [
                'User-Agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/92.0.4515.81 Safari/537.36',
                'x-api-key' => $cursekey
            ];
            $index = $request->page * 10 - 10;
            $addons = Http::accept('application/json')
                ->withHeaders($headers)
                ->get("https://api.curseforge.com/v1/mods/$request->modId/files", [
                    'index' => $index,
                    'pageSize' => 10
                ])
                ->object()
                ->data;

            if ($request->search === '') {
                ModsVersionsResult::where('page', '=', $request->page)
                    ->where('type', '=', 'curseforge')
                    ->delete();

                ModsVersionsResult::create([
                    'page' => $request->page,
                    'modid' => $request->modId,
                    'result' => json_encode($addons),
                    'type' => 'curseforge'
                ]);
            }

            return $addons;
        } else if ($request->type === 'modrinth') {
            $headers = [
                'User-Agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/92.0.4515.81 Safari/537.36'
            ];
            $addons = Http::accept('application/json')
                ->withHeaders($headers)
                ->get("https://api.modrinth.com/v2/project/$request->modId/version")
                ->json();

            $addons = array_slice($addons, $request->page * 10 - 10, 10);

            if ($request->search === '') {
                ModsVersionsResult::where('page', '=', $request->page)
                    ->where('type', '=', 'modrinth')
                    ->delete();

                ModsVersionsResult::create([
                    'page' => $request->page,
                    'modid' => $request->modId,
                    'result' => json_encode($addons),
                    'type' => 'modrinth'
                ]);
            }

            return $addons;
        } else {
            return response()->json([
                'message' => 'Invalid request.'
            ], 400);
        }
    }

    public function getMcModsDescription(Request $request)
    {
        $licenseService = app(LicenseService::class);

        $clientController = new ClientController($licenseService);

        $license = $clientController->checkLicense($request->id , 257 , $request->ip());

        if ($license['message'] !== 'done' ) {
            return $license;
        }

        $cursekey = config('api.cursekey');

        if ($request->type === 'curseforge') {
            $headers = [
                'User-Agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/92.0.4515.81 Safari/537.36',
                'x-api-key' => $cursekey
            ];
            $addons = Http::accept('application/json')
                ->withHeaders($headers)
                ->get("https://api.curseforge.com/v1/mods/$request->modId/description")
                ->object()
                ->data;
        } else if ($request->type === 'modrinth') {
            $addons = Http::accept('application/json')
                ->get("https://api.modrinth.com/v2/project/$request->modId")
                ->object()
                ->body;

            $regex_images = '~https?://\S+?(?:png|gif|jpe?g)~';
            $regex_links = '~(?<!src=\')https?://\S+\b~x';

            $addons = preg_replace($regex_images, "<img src='\\0'>", $addons);
            $addons = preg_replace($regex_links, "<a href='\\0'>\\0</a>", $addons);
            $addons = nl2br($addons);
        } else {
            return response()->json([
                'message' => 'Invalid request.'
            ], 400);
        }

        return $addons;
    }

    public function getMcVersions(Request $request)
    {
        $licenseService = app(LicenseService::class);

        $clientController = new ClientController($licenseService);

        $license = $clientController->checkLicense($request->id , 257 , $request->ip());

        if ($license['message'] !== 'done' ) {
            return $license;
        }

        $versions = Http::get('https://launchermeta.mojang.com/mc/game/version_manifest.json')->json();

        $releases = [];

        foreach ($versions['versions'] as $version) {
            if ($version['type'] === 'release') {
                $releases[] = $version;
            }
        }

        return $releases;
    }
}
