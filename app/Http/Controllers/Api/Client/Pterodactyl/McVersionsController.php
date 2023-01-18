<?php

namespace App\Http\Controllers\Api\Client\Pterodactyl;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\License;
use App\Models\BukkitResult;
use App\Models\PolymartResult;
use Config;

class McVersionsController extends BaseController
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
                if(296 !== $response['resource_id']) {
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

                if(296 !== $addon->name) {
                    return response()->json([
                        'message' => 'Not the good addon.'
                    ], 400);
                }
                return response()->json([
                    'message' => 'Good.',
                    'version' => $addon->version
                ], 200);;
            
        }
    }
    public function getVersions(Request $request) {
        $license = $this->checkLicense($request);
        $versions = null;
        if($license->getStatusCode() === 200) {
            if($request->stype === 'vanilla' || $request->stype === 'snapshot') {
                $versions = Http::get("https://serverjars.com/api/fetchAll/vanilla/$request->stype")->json()['response'];
            } else if($request->stype === 'paper' || $request->stype === 'spigot' || $request->stype === 'purpur' || $request->stype === 'tuinity' || $request->stype === 'sponge') {
                $versions = Http::get("https://serverjars.com/api/fetchAll/servers/$request->stype")->json()['response'];
            } else if($request->stype === 'bungeecord' || $request->stype === 'velocity' || $request->stype === 'waterfall' || $request->stype === 'flamecord') {
                $versions = Http::get("https://serverjars.com/api/fetchAll/proxies/$request->stype")->json()['response'];
            } else if($request->stype === 'mohist' || $request->stype === 'catserver') {
                $versions = Http::get("https://serverjars.com/api/fetchAll/modded/$request->stype")->json()['response'];
            } else if($request->stype === 'forge' || $request->stype === 'fabric' || $request->stype === 'sponge' || $request->stype === 'magma') {
                $maj = ucfirst($request->stype);
                $versions = Http::get("https://cdn.bagou450.com/versions/minecraft/getlist/$maj.json")->json();
            } else if($request->stype === 'modpacks') {
                $maj = ucfirst($request->modpacktype);
                $versions = Http::get("https://cdn.bagou450.com/versions/minecraft/getlist/modpacks/$maj.json")->json();
            } else if($request->stype === 'others') {
                $versions = Http::get("http://$request->url/versionlist.json")->json();
            } else {
                return response()->json([
                    'message' => 'Stype is invalid.'
                ], 400);
            }
            if($versions) {
                return response()->json([
                    'message' => 'Good',
                    'data' => $versions
                ], 200);
            }
            return response()->json([
                'message' => 'Unknow error.'
            ], 400);
        } else {
            return $license;
        };
    }

    public function downloadVersion(Request $request) {
        $license = $this->checkLicense($request);
        if($license->getStatusCode() === 200) {
            $url = '';
            if($request->stype === 'vanilla' || $request->stype === 'snapshot') {
                $url = "https://serverjars.com/api/fetchJar/vanilla/$request->stype/$request->version";
            } else if($request->stype === 'paper' || $request->stype === 'spigot' || $request->stype === 'purpur' || $request->stype === 'tuinity' || $request->stype === 'sponge') {
                $url = "https://serverjars.com/api/fetchJar/servers/$request->stype/$request->version";
            } else if($request->stype === 'bungeecord' || $request->stype === 'velocity' || $request->stype === 'waterfall' || $request->stype === 'flamecord') {
                $url = "https://serverjars.com/api/fetchJar/proxies/$request->stype/$request->version";
            } else if($request->stype === 'mohist' || $request->stype === 'catserver') {
                $url = "https://serverjars.com/api/fetchJar/modded/$request->stype/$request->version";
            } else if($request->stype === 'modpacks' || $request->stype === 'forge' || $request->stype === 'fabric') {
                if(floatval(json_decode($license->getContent())->version) > 4.0) {
                    $url ="https://cdn.bagou450.com/versions/minecraft/$request->stype/$request->version.zip";
                } else {
                    $url ="https://cdn.bagou450.com/versions/minecraft/$request->stype/$request->version.tar.xz";
                }
            } else if($request->stype === 'sponge' || $request->stype === 'magma') {
                $url = "https://cdn.bagou450.com/versions/minecraft/$request->stype/$request->version.jar";
            } else if($request->stype === 'others') {
                $url = "http://$request->url";
            } else {
                return response()->json([
                    'message' => 'Stype is invalid.'
                ], 400);;
            }

            if($url !== '') {
			$url = str_replace(' ', '%20', $url);
			
                return response()->json([
                    'message' => 'Good',
                    'data' => $url,
                    'size' => get_headers($url, true)['Content-Length'],
                    'version' => floatval(json_decode($license->getContent())->version)
                ], 200);
            }
            return response()->json([
                'message' => 'Unknow error.'
            ], 400);

        } else {
            return $license;
        };

    }

}
