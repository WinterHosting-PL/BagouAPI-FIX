<?php

namespace App\Http\Controllers\Api\Client\Pterodactyl;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\License;
use App\Models\ModsResult;
use App\Models\ModsVersionsResult;
use Config;

class McModsController extends BaseController
{
    function addtodb(Request $request) {
        $key = config('api.key');
        $response = Http::withHeaders(['Authorization' => $key, 'transactionid' => $request->id])
        ->post('https://pterodactylmarket.com/api/seller/transaction?Authorization')
        ->json();
        if($response['valid'] && $response['transaction_status'] == "COMPLETE") {
            $addon = Http::withHeaders(['resourceid' => $response['resource_id'], "query" => "info"])
            ->post('https://pterodactylmarket.com/api/public/resource')
            ->json();
            $user = Http::withHeaders(['Authorization' => $key, "userid" => $response['buyer_id']])
            ->post('https://pterodactylmarket.com/api/seller/user')
            ->json();
            License::create(['blacklisted' => $user['banned'], 'buyer' => $user['username'], 'fullname' => $addon['name'], 'ip' => [$request->ip()], 'maxusage' => 2, 'name' => $response['resource_id'], 'transaction' => $request->id, 'usage' => 1, "buyerid" => $response['buyer_id']]);
           if($user['banned']) {
                return response()->json([
                    'message' => 'User blacklisted.'
                ], 418);
            } else {
                if(257 !== $response['resource_id']) {
                    return response()->json([
                        'message' => 'Not the good addon.'
                    ], 400);
                }
                if($addon->usage > $addon->maxusage) {
                    return response()->json([
                        'message' => 'Too many usage.'
                    ], 400);
                }
                return response()->json([
                    'message' => 'Good.'
                ], 200);;
            }
        } else {
            return response()->json([
                'message' => 'Transaction is not valid.'
            ], 400);
        }
    }
    function checkLicense(Request $request) {
        $addon = License::where("transaction", '=', $request->id)->first();
        if (!$addon) {
            return $this->addtodb($request);
        } else {
            $key = config('api.key');

            if($addon->blacklisted) {
                return response()->json([
                    'message' => 'User blacklisted.'
                ], 418);
            }
            

    
                if(257 !== $addon->name) {
                    return response()->json([
                        'message' => 'Not the good addon.'
                    ], 400);
                }
                return response()->json([
                    'message' => 'Good.'
                ], 200);;
            
        }
    }
    public function getMcMods(Request $request) {
        $license = $this->checkLicense($request);
        if($license->getStatusCode() === 200) {
            if(!$request->loaders) {
                $request->loaders = false;
            }
            if(!$request->game_versions) {
                $request->game_versions = false;
            }
            $data = ModsResult::where(['page' => $request->page, 'type' => $request->type])->first();
            if($data && $request->search == '' && !$request->game_versions && !$request->loaders) {
                if($data->updated_at->diffInHours(now())<24) {
                    return $data->result;
                }
            }
            
            if($request->type == 'curseforge') {

                $cursekey = config('api.cursekey');
                $headers = ['User-Agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/92.0.4515.81 Safari/537.36', 'x-api-key' => $cursekey];
                if($request->loaders) {
                    $maj = 1;
                    if($request->loaders === 'fabric') {
                        $maj = 4;
                    }
                    if(!$request->game_versions) {
                        
                        $addons = Http::accept('application/json')->withHeaders($headers)->get('https://api.curseforge.com/v1/mods/search', ['index' => $request->page*20-20, 'pageSize' => 20, 'gameId' => 432, 'classId' => 6, 'searchFilter' => $request->search, 'sortField' => 2, 'sortOrder' => 'desc'])
                        ->object()->data;
                    } else {
                        return Http::accept('application/json')->withHeaders($headers)->get('https://api.curseforge.com/v1/mods/search', ['index' => $request->page*20-20, 'pageSize' => 20, 'gameId' => 432, 'classId' => 6, 'searchFilter' => $request->search, 'sortField' => 2, 'sortOrder' => 'desc', 'gameVersion' => $request->game_versions, 'modLoaderType' => $maj ])
                        ->object()->data;
                    
                    }
                }
                else if ($request->game_versions) {

                    return Http::accept('application/json')->withHeaders($headers)->get('https://api.curseforge.com/v1/mods/search', ['index' => $request->page*20-20, 'pageSize' => 20, 'gameId' => 432, 'classId' => 6, 'searchFilter' => $request->search, 'sortField' => 2, 'sortOrder' => 'desc', 'gameVersion' => $request->game_versions ])
                    ->object()->data;
                } else if (!$request->game_versions && !$request->loaders) {

                    $addons = Http::accept('application/json')->withHeaders($headers)->get('https://api.curseforge.com/v1/mods/search', ['index' => $request->page*20-20, 'pageSize' => 20, 'gameId' => 432, 'classId' => 6, 'searchFilter' => $request->search, 'sortField' => 2, 'sortOrder' => 'desc' ])
                    ->object()->data;
                }
            } else if ($request->type == 'modrinth') {
                $headers = ['User-Agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/92.0.4515.81 Safari/537.36'];
                if($request->game_versions && $request->loaders) {
                    return Http::accept('application/json')->withHeaders($headers)->get('https://api.modrinth.com/v2/search', ['offset' => $request->page*20-20, 'limit' => 20, 'query' => $request->search, 'facets' => "[[\"categories:$request->loaders\"],[\"versions:$request->game_versions\"],[\"project_type:mod\"]]"])
                    ->object()->hits;
                } else if($request->game_versions) {
                    return Http::accept('application/json')->withHeaders($headers)->get('https://api.modrinth.com/v2/search', ['offset' => $request->page*20-20, 'limit' => 20, 'query' => $request->search, 'facets' => "[[\"versions:$request->game_versions\"],[\"project_type:mod\"]]"])
                    ->object()->hits;
                } else if($request->loaders) {
                    return Http::accept('application/json')->withHeaders($headers)->get('https://api.modrinth.com/v2/search', ['offset' => $request->page*20-20, 'limit' => 20, 'query' => $request->search, 'facets' => "[[\"categories:$request->loaders\"],[\"project_type:mod\"]]"])
                    ->object()->hits;
                } else {
                    $addons = Http::accept('application/json')->withHeaders($headers)->get('https://api.modrinth.com/v2/search', ['offset' => $request->page*20-20, 'limit' => 20, 'query' => $request->search, 'facets' => "[[\"project_type:mod\"]]"])
                    ->object()->hits;
                }
                

            } else {
                return response()->json([
                    'message' => 'Invalid request.'
                ], 400);
            };
            if($request->search == '') {
                ModsResult::where('page', '=', $request->page)->where('type', '=', $request->type)->delete();
                ModsResult::create(['page' => $request->page, 'result' => json_encode($addons), 'type' => $request->type]);
            }
            return $addons;
        } else {
            return $license;
        };
    }

    public function getMcModsVersions(Request $request) {
        $license = $this->checkLicense($request);
        if($license->getStatusCode() === 200) {
            $data = ModsVersionsResult::where(['page' => $request->page, 'modid' => $request->modId, 'type' => $request->type])->first();
            if($data && $request->search == '') {
                if($data->updated_at->diffInHours(now())<24) {
                    return $data->result;
                }
            }
            if($request->type == 'curseforge') {

            $cursekey = config('api.cursekey');
            $headers = ['User-Agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/92.0.4515.81 Safari/537.36', 'x-api-key' => $cursekey];
            $index = $request->page*10-10;
            $addons = Http::accept('application/json')->withHeaders($headers)->get("https://api.curseforge.com/v1/mods/$request->modId/files?index=$index&pageSize=10")
                ->object()->data;
            if($request->search == '') {
                ModsVersionsResult::where('page', '=', $request->page)->where('type', '=', 'curseforge')->delete();
                ModsVersionsResult::create(['page' => $request->page, 'modid' => $request->modId, 'result' => json_encode($addons), 'type' => 'curseforge']);
            }
            return $addons;
        } else if ($request->type == 'modrinth') {
            $headers = ['User-Agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/92.0.4515.81 Safari/537.36'];
            $addons = Http::accept('application/json')->withHeaders($headers)->get("https://api.modrinth.com/v2/project/$request->modId/version")
                ->json();
            $addons = array_slice($addons, $request->page*10-10, 10);
            if($request->search == '') {
                ModsVersionsResult::where('page', '=', $request->page)->where('type', '=', 'modrinth')->delete();
                ModsVersionsResult::create(['page' => $request->page, 'modid' => $request->modId, 'result' => json_encode($addons), 'type' => 'modrinth']);
            }

            return $addons;
        } else {
            return response()->json([
                'message' => 'Invalid request.'
            ], 400);
        };
        } else {
            return $license;
        };
    }
    public function getMcModsDescription(Request $request) {
        $license = $this->checkLicense($request);
        if($license->getStatusCode() === 200) {
            $cursekey = config('api.cursekey');
            if($request->type == 'curseforge') {
                $headers = ['User-Agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/92.0.4515.81 Safari/537.36', 'x-api-key' => $cursekey];
                $addons = Http::accept('application/json')->withHeaders($headers)->get("https://api.curseforge.com/v1/mods/$request->modId/description")
                    ->object()->data;
            } else if ($request->type == 'modrinth') {
                $addons = Http::accept('application/json')->get("https://api.modrinth.com/v2/project/$request->modId")
                ->object()->body;

                $regex_images = '~https?://\S+?(?:png|gif|jpe?g)~';
                $regex_links = '~
                (?<!src=\') # negative lookbehind (no src=\' allowed!)
                https?://   # http:// or https://
                \S+         # anything not a whitespace
                \b          # a word boundary
                ~x';        # verbose modifier for these explanations

                $addons = preg_replace($regex_images, "<img src='\\0'>", $addons);
                $addons = preg_replace($regex_links, "<a href='\\0'>\\0</a>", $addons);
                $addons = nl2br($addons);
            } else {
                return response()->json([
                    'message' => 'Invalid request.'
                ], 400);
            };
            return $addons;
        } else {
            return $license;
        };
    }
    public function getMcVersions(Request $request) {
        $license = $this->checkLicense($request);
        if($license->getStatusCode() === 200) {
        $versions = Http::get('https://launchermeta.mojang.com/mc/game/version_manifest.json')->json();
        $releases = array();
        foreach($versions['versions'] as $version) {
            if($version['type'] === 'release') {
                array_push($releases, $version);
            }
        }
        return $releases;
    } else {
        return $license;
    };
    }

}

