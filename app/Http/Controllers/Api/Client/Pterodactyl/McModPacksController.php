<?php

namespace App\Http\Controllers\Api\Client\Pterodactyl;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\License;
use App\Models\ModPacksResult;
use App\Models\ModsVersionsResult;
use Config;
use Weidner\Goutte\GoutteFacade;

class McModPacksController extends BaseController
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
           // return true;
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
    public function getMcModPacks(Request $request) {
        
        if(true) {
      //$license = $this->checkLicense($request);
        //if($license->getStatusCode() === 200 ||) {
            if(!$request->loaders) {
                $request->loaders = false;
            }
            if(!$request->game_versions) {
                $request->game_versions = false;
            }
            /*$data = ModPacksResult::where(['page' => $request->page, 'type' => $request->type])->first();
            if($data && $request->search == '' && !$request->game_versions) {
                if($data->updated_at->diffInHours(now())<24) {
                    return $data->result;
                }
            }*/
            
            if($request->type == 'curseforge') {

                $cursekey = config('api.cursekey');
                $headers = ['User-Agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/92.0.4515.81 Safari/537.36', 'x-api-key' => $cursekey];
                if ($request->game_versions) {
                    return Http::accept('application/json')->withHeaders($headers)->get('https://api.curseforge.com/v1/mods/search', ['index' => $request->page*20-20, 'pageSize' => 20, 'gameId' => 432, 'classId' => 4471, 'searchFilter' => $request->search, 'sortField' => 2, 'sortOrder' => 'desc', 'gameVersion' => $request->game_versions ])
                    ->object()->data;
                } else if (!$request->game_versions && !$request->loaders) {
                    $modpackslist = Http::accept('application/json')->withHeaders($headers)->get('https://api.curseforge.com/v1/mods/search', ['index' => $request->page*20-20, 'pageSize' => 20, 'gameId' => 432, 'classId' => 4471, 'searchFilter' => $request->search, 'sortField' => 2, 'sortOrder' => 'desc' ])
                    ->object()->data;
                }
            } else if ($request->type == 'modrinth') {
                $headers = ['User-Agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/92.0.4515.81 Safari/537.36'];
                if($request->game_versions) {
                    return Http::accept('application/json')->withHeaders($headers)->get('https://api.modrinth.com/v2/search', ['offset' => $request->page*20-20, 'limit' => 20, 'query' => $request->search, 'facets' => "[[\"versions:$request->game_versions\"],[\"project_type:modpack\"]]"])
                    ->object()->hits;
                } else if($request->loaders) {
                    return Http::accept('application/json')->withHeaders($headers)->get('https://api.modrinth.com/v2/search', ['offset' => $request->page*20-20, 'limit' => 20, 'query' => $request->search, 'facets' => "[[\"categories:$request->loaders\"],[\"project_type:modpack\"]]"])
                    ->object()->hits;
                } else {
                    $modpackslist = Http::accept('application/json')->withHeaders($headers)->get('https://api.modrinth.com/v2/search', ['offset' => $request->page*20-20, 'limit' => 20, 'query' => $request->search, 'facets' => "[[\"project_type:modpack\"]]"])
                    ->object()->hits;
                }
            } else if ($request->type == 'ftb') {
                $headers = ['User-Agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/92.0.4515.81 Safari/537.36'];
                if($request->search) { 
                    $tmplist = Http::accept('application/json')->withHeaders($headers)->get("https://api.modpacks.ch/public/modpack/search/250?term=$request->search")
                    ->object()->hits;
                } else {
                    $tmplist = Http::accept('application/json')->withHeaders($headers)->get('https://api.modpacks.ch/public/modpack/popular/installs/250')
                    ->object();
                }
                $tmplist = array_slice($tmplist->packs, $request->page*20-20, 20);
                $modpackslist = array();
                foreach($tmplist as $modpack) {
                    $modpackget = Http::accept('application/json')->withHeaders($headers)->get("https://api.modpacks.ch/public/modpack/$modpack")->object();  
                    $versions = array();    
                    $loader = '';
                    if(isset($modpackget->versions)) {
                        if(count($modpackget->versions) > 0) {
                            if(isset($modpackget->versions[0]->targets)) {
                                foreach($modpackget->versions[0]->targets as $version) {
                                    if($version->type == 'modloader') {
                                        $loader = $version->name;
                                    }
                                    if($version->type == 'game') {
                                        array_push($versions, $version->version);
                                    }
                                }
                            }
                        }
                    }       
                    if(isset(($modpackget->name))) {
                        array_push($modpackslist, [
                            'name' => $modpackget->name,
                            'smalldesc' => isset($modpackget->synopsis) ? $modpackget->synopsis : '',
                            'description' => isset($modpackget->description) ? $modpackget->description : '',
                            'icon' => isset($modpackget->art) ? $modpackget->art[0]->url : '',
                            'author' => isset($modpackget->authors) ? $modpackget->authors[0]->name : '',
                            'versions' => $versions,
                            'loader' => $loader,
                            'installations' => isset($modpackget->installs) ? $modpackget->installs : '',
                            'plays' => isset($modpackget->plays) ? $modpackget->plays : '',
                            'tags' => isset($modpackget->tags) ? $modpackget->tags : '',
                            'id' => isset($modpackget->id) ? $modpackget->id : '',
                        ]);
                    }       
                    
                }
                if($request->loaders && $request->loaders !== '') {
                    $tmplist = array();
                    foreach($modpackslist as $modpack) {
                        if(strpos($modpack['loader'], $request->loaders) !== false) {
                            array_push($tmplist, $modpack);
                        };
                    };
                    $modpackslist = $tmplist;
                }
                if($request->game_versions && $request->game_versions !== '') {
                    $tmplist = array();
                    foreach($modpackslist as $modpack) {
                        if(in_array($request->game_versions, $modpack['versions']) !== false) {
                            array_push($tmplist, $modpack);
                        };
                    };
                    $modpackslist = $tmplist;
                }
            } else if ($request->type == 'technicpack') {
                $url = "https://www.technicpack.net/modpacks?&q=$request->search&page=$request->page";
                $modpacksscrap = GoutteFacade::request('GET', $url); 
                //dd($versionslist);
                global $modpackslist;
                $modpackslist = array();
                global $tmpmodpacks;
                $tmpmodpacks = array();
                global $lastbuild;
                $lastbuild = Http::get('http://api.technicpack.net/launcher/version/stable4')->object()->build;
                $modpacksscrap->filter("div.modpack-item")->each(function ($item) {
                    global $link;
                    global $icon;
                    global $downloadcount;
                    $link = '';
                    $icon = '';
                    $downloadcount = '';
                    $item->filter("div.modpack-image > a")->each(function ($item_link) {
                        global $link;
                        $link = $item_link->attr('href');
                        $item_link->filter('img')->each(function ($item_avatar) {
                            global $icon;
                            $icon = $item_avatar->attr('src');
                            $title = $item_avatar->attr('title');

                        });
                    });
                    $item->filter("div.modpack-stats > span.stat.downloads")->each(function ($item_downloads) {
                        global $downloadcount;
                        $downloadcount = $item_downloads->text();
                    });
                    global $tmpmodpacks;
                    $tokens = explode('/', $link);
                    $str = trim(end($tokens));
                    $tokens = explode('.', $str);
                    $str = $tokens[0];
                    global $lastbuild;
                    $downloadlink = "https://api.technicpack.net/modpack/" . $str . "?build=$lastbuild";
                    array_push($tmpmodpacks, array(
                    'link' => $link, 
                    'icon' => $icon,
                    'downloadcount' => $downloadcount,
                    'downloadlink' => $downloadlink,
                    ));
                });
                foreach($tmpmodpacks as $modpack) {

                    $modpacksscrap = GoutteFacade::request('GET', $modpack['link']); 
                    global $name;
                    global $versions;
                    global $author;
                    $name = '';
                    $versions = '';
                    $author = '';
                    $modpacksscrap->filter("div.modpack-title")->each(function ($item) {
                        $item->filter("h1")->each(function ($item_name) {
                            global $name;
                            $name = $item_name->text();
                        });
                        
                        $item->filter("div.modpack-meta")->each(function ($item_versions) {
                            global $versions;
                            $versions = explode(' using ', explode('Minecraft ', $item_versions->text())[1])[0];
                            global $tmp;
                            $tmp = false;
                            $item_versions->filter("a")->each(function ($item_versions) {
                                global $tmp;
                                global $author;
                                if(!$tmp) {
                                    $tmp = true;
                                    $author = $item_versions->text();
                                };
                            });
                        });
                    });
                    global $modpackslist;
                    array_push($modpackslist, array(
                        'name' => $name,
                        'versions' => [$versions],
                        'author' => $author,
                        'link' => $modpack['link'], 
                        'icon' => $modpack['icon'],
                        'downloadcount' => $modpack['downloadcount'],
                        'downloadlink' => $modpack['downloadlink'],
                       
                    ));
                }
             } else if($request->type == 'voidswrath') {
                $url = "https://voidswrath.com/mod-packs/";
                $modpacksscrap = GoutteFacade::request('GET', $url);
                
                global $tmppack; 
                $tmppack = array();
                $modpacksscrap->filter("div.mod-packs > ul > li > a")->each(function ($item) {
                    global $name;
                    global $versions;
                    global $icon;
                    global $link;
                    $name = '';
                    $versions = '';
                    $icon = '';
                    $link = $item->attr('href');
                    $item->filter("div.mod-pack-thumb")->each(function ($item_icon) {
                        global $icon;
                        $icon = explode("')", explode("image:url('", $item_icon->attr('style'))[1])[0];
                        $item_icon->filter("div.mod-pack-title-list")->each(function ($item_name) {
                            global $name;
                            $name = $item_name->text();
                        });
                        $item_icon->filter("div.mod-pack-list-details > ul > li")->each(function ($item_versions) {
                            if(str_contains($item_versions->text(), 'Minecraft')) {
                                global $versions;
                                $versions = explode(': ', $item_versions->text())[1];
                            }
                        });
                    });
                    global $tmppack; 
                    array_push($tmppack, array(
                        'name' => $name,
                        'versions' => $versions,
                        'icon' => $icon,
                        'link' => $link
                    ));
                });
                global $modpackslist; 
                $modpackslist = array();
                foreach($tmppack as $modpack) {
                    $url = $modpack['link'];
                    $modpacksscrap = GoutteFacade::request('GET', $url);
                    global $downloadlink;
                    $downloadlink = '';
                    $modpacksscrap->filter("a.more-details-installer")->each(function ($item) {
                        if($item->text() == 'Download the Server Pack') {
                            global $downloadlink;
                            $downloadlink = $item->attr('href');
                        }
                    });
                    $modpack['downloadlink'] = $downloadlink;
                    array_push($modpackslist, array(
                        'name' => $modpack['name'],
                        'versions' => $modpack['versions'],
                        'icon' => $modpack['icon'],
                        'link' => $modpack['link'],
                        'downloadlink' => $modpack['downloadlink']
                    ));

                }
             } else {
                return response()->json([
                    'message' => 'Invalid request.'
                ], 400);
            };
            if($request->search == '') {
                ModPacksResult::where('page', '=', $request->page)->where('type', '=', $request->type)->delete();
                ModPacksResult::create(['page' => $request->page, 'result' => json_encode($modpackslist), 'type' => $request->type]);
            }
            return $modpackslist;
        } else {
            return $license;
        };
    }

    public function getMcModPacksVersions(Request $request) {
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
                ModPacksVersionsResult::where('page', '=', $request->page)->where('type', '=', 'curseforge')->delete();
                ModPacksVersionsResult::create(['page' => $request->page, 'modid' => $request->modId, 'result' => json_encode($addons), 'type' => 'curseforge']);
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

