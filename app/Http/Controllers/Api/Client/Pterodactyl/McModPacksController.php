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
use App\Models\ModPacksDownloadLinks;
use Config;
use Weidner\Goutte\GoutteFacade;
use Illuminate\Support\Facades\Redirect;

class McModPacksController extends BaseController
{

    public function getMcModPacks(Request $request)
    {
        $clientController = new ClientController();
        $license = $clientController->checkLicense($request->id, 327, $request->ip());
        if ($license->getStatusCode() === 200) {
            if (!$request->loaders) {
                $request->loaders = false;
            }
            if (!$request->game_versions) {
                $request->game_versions = false;
            }
            /* $data = ModPacksResult::where(['page' => $request->page, 'type' => $request->type])->first();
            if($data && $request->search == '' && !$request->game_versions) {
            if($data->updated_at->diffInHours(now())<24) {
            return json_decode($data->result, true);
            }
            }*/

            if ($request->type == 'curseforge') {

                $cursekey = config('api.cursekey');
                $headers = ['User-Agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/92.0.4515.81 Safari/537.36', 'x-api-key' => $cursekey];
                if ($request->game_versions) {
                    return Http::accept('application/json')->withHeaders($headers)->get('https://api.curseforge.com/v1/mods/search', ['index' => $request->page * 20 - 20, 'pageSize' => 20, 'gameId' => 432, 'classId' => 4471, 'searchFilter' => $request->search, 'sortField' => 2, 'sortOrder' => 'desc', 'gameVersion' => $request->game_versions])
                        ->object()->data;
                } else if (!$request->game_versions && !$request->loaders) {
                    $modpackslist = Http::accept('application/json')->withHeaders($headers)->get('https://api.curseforge.com/v1/mods/search', ['index' => $request->page * 20 - 20, 'pageSize' => 20, 'gameId' => 432, 'classId' => 4471, 'searchFilter' => $request->search, 'sortField' => 2, 'sortOrder' => 'desc'])
                        ->object()->data;
                }
            } else if ($request->type == 'modrinth') {
                $headers = ['User-Agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/92.0.4515.81 Safari/537.36'];
                if ($request->game_versions) {
                    return Http::accept('application/json')->withHeaders($headers)->get('https://api.modrinth.com/v2/search', ['offset' => $request->page * 20 - 20, 'limit' => 20, 'query' => $request->search, 'facets' => "[[\"versions:$request->game_versions\"],[\"project_type:modpack\"]]"])
                        ->object()->hits;
                } else if ($request->loaders) {
                    return Http::accept('application/json')->withHeaders($headers)->get('https://api.modrinth.com/v2/search', ['offset' => $request->page * 20 - 20, 'limit' => 20, 'query' => $request->search, 'facets' => "[[\"categories:$request->loaders\"],[\"project_type:modpack\"]]"])
                        ->object()->hits;
                } else {
                    $modpackslist = Http::accept('application/json')->withHeaders($headers)->get('https://api.modrinth.com/v2/search', ['offset' => $request->page * 20 - 20, 'limit' => 20, 'query' => $request->search, 'facets' => "[[\"project_type:modpack\"]]"])
                        ->object()->hits;
                }
            } else if ($request->type == 'ftb') {
                $headers = ['User-Agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/92.0.4515.81 Safari/537.36'];
                if ($request->search) {
                    $tmplist = Http::accept('application/json')->withHeaders($headers)->get("https://api.modpacks.ch/public/modpack/search/250?term=$request->search")
                        ->object()->hits;
                } else {
                    $tmplist = Http::accept('application/json')->withHeaders($headers)->get('https://api.modpacks.ch/public/modpack/popular/installs/250')
                        ->object();
                }
                $tmplist = array_slice($tmplist->packs, $request->page * 20 - 20, 20);
                $modpackslist = array();
                foreach ($tmplist as $modpack) {
                    $modpackget = Http::accept('application/json')->withHeaders($headers)->get("https://api.modpacks.ch/public/modpack/$modpack")->object();
                    $versions = array();
                    $loader = '';
                    if (isset($modpackget->versions)) {
                        if (count($modpackget->versions) > 0) {
                            if (isset($modpackget->versions[0]->targets)) {
                                foreach ($modpackget->versions[0]->targets as $version) {
                                    if ($version->type == 'modloader') {
                                        $loader = $version->name;
                                    }
                                    if ($version->type == 'game') {
                                        array_push($versions, $version->version);
                                    }
                                }
                            }
                        }
                    }
                    $tags = array();
                    if (isset($modpackget->tags)) {
                        foreach ($modpackget->tags as $tag) {
                            array_push($tags, $tag->name);
                        }
                    }
                    if (!isset($modpackget->versions)) {
                        continue;
                    }
                    $versionid = $modpackget->versions[0]->id;
                    if (isset($modpackget->name)) {
                        array_push($modpackslist, [
                            'name' => $modpackget->name,
                            'summary' => isset($modpackget->synopsis) ? $modpackget->synopsis : '',
                            'description' => isset($modpackget->description) ? $modpackget->description : '',
                            'logo' => array('thumbnailUrl' => isset($modpackget->art) ? $modpackget->art[0]->url : ''),
                            'author' => isset($modpackget->authors) ? $modpackget->authors[0]->name : '',
                            'versions' => $versions,
                            'downloadlink' => "https://api.modpacks.ch/public/modpack/$modpackget->id/$versionid/server/linux",
                            'versionid' => $versionid,
                            'loader' => $loader,
                            'installations' => isset($modpackget->installs) ? $modpackget->installs : '',
                            'plays' => isset($modpackget->plays) ? $modpackget->plays : '',
                            'display_categories' => $tags,
                            'id' => isset($modpackget->id) ? $modpackget->id : '',
                            'link' => isset($modpackget->id) ? "https://www.feed-the-beast.com/modpacks/" . $modpackget->id : '',
                        ]);
                    }

                }
                if ($request->loaders && $request->loaders !== '') {
                    $tmplist = array();
                    foreach ($modpackslist as $modpack) {
                        if (strpos($modpack['loader'], $request->loaders) !== false) {
                            array_push($tmplist, $modpack);
                        }
                        ;
                    }
                    ;
                    $modpackslist = $tmplist;
                }
                if ($request->game_versions && $request->game_versions !== '') {
                    $tmplist = array();
                    foreach ($modpackslist as $modpack) {
                        if (in_array($request->game_versions, $modpack['versions']) !== false) {
                            array_push($tmplist, $modpack);
                        }
                        ;
                    }
                    ;
                    $modpackslist = $tmplist;
                }
            } else if ($request->type == 'technicpack') {
                $url = "https://www.technicpack.net/modpacks/sort-by/popular?&q=$request->search&page=$request->page";
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
                    global $downloadcount;
                    $link = '';
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
                    array_push(
                        $tmpmodpacks,
                        array(
                            'link' => $link,
                            'downloadcount' => $downloadcount,
                            'downloadlink' => $downloadlink,
                        )
                    );
                });
                foreach ($tmpmodpacks as $modpack) {

                    $modpacksscrap = GoutteFacade::request('GET', $modpack['link']);
                    global $name;
                    global $versions;
                    global $description;
                    global $author;
                    global $icon;
                    $name = '';
                    $description = '';
                    $versions = '';
                    $author = '';
                    $icon = '';

                    $modpacksscrap->filter("div.modpack-title")->each(function ($item) {
                        $item->filter("h1")->each(function ($item_name) {
                            global $name;
                            $name = $item_name->text();
                            $item_name->filter("img")->each(function ($item_icon) {
                                global $icon;
                                $icon = $item_icon->attr('src');
                            });
                        });

                        $item->filter("div.modpack-meta")->each(function ($item_versions) {
                            global $versions;
                            $versions = explode(' using ', explode('Minecraft ', $item_versions->text())[1])[0];
                            global $tmp;
                            $tmp = false;
                            $item_versions->filter("a")->each(function ($item_versions) {
                                global $tmp;
                                global $author;
                                if (!$tmp) {
                                    $tmp = true;
                                    $author = $item_versions->text();
                                };
                            });
                        });
                    });
                    $modpacksscrap->filter('div.modpack-overview-content')->each(function ($item) {
                        global $description;
                        $description = $item->html();

                    });
                    $downloadlink = ModPacksDownloadLinks::where('link', '=', $modpack['link'])->where('type', '=', 'technicpack')->first();
                    if ($downloadlink || $downloadlink !== null) {
                        $modpack['downloadlink'] = $downloadlink->downloadlink;
                    }
                    if (str_starts_with($modpack['downloadlink'], 'https://api.technicpack.net')) {
                        $data = Http::get($modpack['downloadlink'])->object();
                        if (isset($data->serverPackUrl)) {
                            if ($data->serverPackUrl && $data->serverPackUrl !== null) {
                                $modpack['downloadlink'] = $data->serverPackUrl;
                            }
                        }

                    }
                    global $modpackslist;
                    if (!str_starts_with($modpack['downloadlink'], 'https://api.technicpack.net'))
                        array_push(
                            $modpackslist,
                            array(
                                'name' => $name,
                                'versions' => [$versions],
                                'author' => $author,
                                'link' => $modpack['link'],
                                'description' => $description,
                                'logo' => array('thumbnailUrl' => $icon),
                                'downloadcount' => $modpack['downloadcount'],
                                'downloadlink' => $modpack['downloadlink'],

                            )
                        );
                }
            } else if ($request->type == 'voidswrath') {
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
                            if (str_contains($item_versions->text(), 'Minecraft')) {
                                global $versions;
                                $versions = explode(': ', $item_versions->text())[1];
                            }
                        });
                    });
                    global $tmppack;
                    array_push(
                        $tmppack,
                        array(
                            'name' => $name,
                            'versions' => $versions,
                            'logo' => $icon,
                            'link' => $link
                        )
                    );
                });
                global $modpackslist;
                $modpackslist = array();
                foreach ($tmppack as $modpack) {
                    if (str_contains(strtolower($modpack['name']), $request->search)) {
                        $url = $modpack['link'];
                        $modpacksscrap = GoutteFacade::request('GET', $url);
                        global $downloadlink;
                        $downloadlink = '';
                        global $description;
                        $description;
                        $modpacksscrap->filter("a.more-details-installer")->each(function ($item) {
                            if ($item->text() == 'Download the Server Pack') {
                                global $downloadlink;
                                $downloadlink = $item->attr('href');
                            }
                        });
                        $modpacksscrap->filter("div.post-server-content")->each(function ($item) {
                            global $description;
                            $description = $item->html();
                        });
                        $modpack['downloadlink'] = $downloadlink;
                        array_push(
                            $modpackslist,
                            array(
                                'name' => $modpack['name'],
                                'description' => $description,
                                'versions' => $modpack['versions'],
                                'logo' => array('thumbnailUrl' => $modpack['logo']),
                                'link' => $modpack['link'],
                                'downloadlink' => $modpack['downloadlink']
                            )
                        );

                    }
                }
            } else {
                return response()->json([
                    'message' => 'Invalid request.'
                ], 400);
            }
            ;
            if ($request->search == '') {
                ModPacksResult::where('page', '=', $request->page)->where('type', '=', $request->type)->delete();
                ModPacksResult::create(['page' => $request->page, 'result' => json_encode($modpackslist), 'type' => $request->type]);
            }
            return $modpackslist;
        } else {
            return $license;
        }
        ;
    }

    public function getMcModPacksVersions(Request $request)
    {
        $clientController = new ClientController();
        $license = $clientController->checkLicense($request->id, 327, $request->ip());
        if ($license->getStatusCode() === 200) {
            $data = ModsVersionsResult::where(['page' => $request->page, 'modid' => $request->modId, 'type' => $request->type])->first();
            if ($data && $request->search == '') {
                if ($data->updated_at->diffInHours(now()) < 24) {
                    return $data->result;
                }
            }
            if ($request->type == 'curseforge') {

                $cursekey = config('api.cursekey');
                $headers = ['User-Agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/92.0.4515.81 Safari/537.36', 'x-api-key' => $cursekey];
                $index = $request->page * 10 - 10;
                $addons = Http::accept('application/json')->withHeaders($headers)->get("https://api.curseforge.com/v1/mods/$request->modId/files?index=$index&pageSize=10")
                    ->object()->data;
                if ($request->search == '') {
                    ModPacksVersionsResult::where('page', '=', $request->page)->where('type', '=', 'curseforge')->delete();
                    ModPacksVersionsResult::create(['page' => $request->page, 'modid' => $request->modId, 'result' => json_encode($addons), 'type' => 'curseforge']);
                }
                return $addons;
            } else if ($request->type == 'modrinth') {
                $headers = ['User-Agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/92.0.4515.81 Safari/537.36'];
                $addons = Http::accept('application/json')->withHeaders($headers)->get("https://api.modrinth.com/v2/project/$request->modId/version")
                    ->json();
                $addons = array_slice($addons, $request->page * 10 - 10, 10);
                if ($request->search == '') {
                    ModsVersionsResult::where('page', '=', $request->page)->where('type', '=', 'modrinth')->delete();
                    ModsVersionsResult::create(['page' => $request->page, 'modid' => $request->modId, 'result' => json_encode($addons), 'type' => 'modrinth']);
                }

                return $addons;
            } else {
                return response()->json([
                    'message' => 'Invalid request.'
                ], 400);
            }
            ;
        } else {
            return $license;
        }
        ;
    }
    public function getMcModpacksDescription(Request $request)
    {
        $clientController = new ClientController();
        $license = $clientController->checkLicense($request->id, 327, $request->ip());
        if ($license->getStatusCode() === 200) {
            $cursekey = config('api.cursekey');
            if ($request->type == 'curseforge') {
                $headers = ['User-Agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/92.0.4515.81 Safari/537.36', 'x-api-key' => $cursekey];
                $addons = Http::accept('application/json')->withHeaders($headers)->get("https://api.curseforge.com/v1/mods/$request->modpackId/description")
                    ->object()->data;
            } else if ($request->type == 'modrinth') {
                $addons = Http::accept('application/json')->get("https://api.modrinth.com/v2/project/$request->modpackId")
                    ->object()->body;

                $regex_images = '~https?://\S+?(?:png|gif|jpe?g)~';
                $regex_links = '~
                (?<!src=\') # negative lookbehind (no src=\' allowed!)
                https?://   # http:// or https://
                \S+         # anything not a whitespace
                \b          # a word boundary
                ~x'; # verbose modifier for these explanations

                $addons = preg_replace($regex_images, "<img src='\\0'>", $addons);
                $addons = preg_replace($regex_links, "<a href='\\0'>\\0</a>", $addons);
                $addons = nl2br($addons);
            } else {
                return response()->json([
                    'message' => 'Invalid request.'
                ], 400);
            }
            ;
            return $addons;
        } else {
            return $license;
        }
        ;
    }
    public function getMcVersions(Request $request)
    {
        $clientController = new ClientController();
        $license = $clientController->checkLicense($request->id, 327, $request->ip());
        if ($license->getStatusCode() === 200) {
            $versions = Http::get('https://launchermeta.mojang.com/mc/game/version_manifest.json')->json();
            $releases = array();
            foreach ($versions['versions'] as $version) {
                if ($version['type'] === 'release') {
                    array_push($releases, $version);
                }
            }
            return $releases;
        } else {
            return $license;
        }
        ;
    }
    public function download(Request $request)
    {
        $clientController = new ClientController();
        $license = $clientController->checkLicense($request->id, 327, $request->ip());
        if ($license->getStatusCode() === 200) {
            if ($request->type == 'voidswrath') {
                return response()->json([
                    'message' => 'Good',
                    'data' => $request->data,
                    'size' => get_headers($request->data, true)['Content-Length'],
                ], 200);
            } else if ($request->type == 'curseforge') {
                $cursekey = config('api.cursekey');
                $headers = ['User-Agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/92.0.4515.81 Safari/537.36', 'x-api-key' => $cursekey];
                $modpackid = $request->modpackid;
                $modpackserverfileid = null;
                $versionslist = Http::accept('application/json')->withHeaders($headers)->get("https://api.curseforge.com/v1/mods/$modpackid/files?pageSize=10")->object()->data;
                $nb = 0;
                while ($modpackserverfileid == null) {
                    if (sizeof($versionslist) == $nb) {
                        return response()->json([
                            'message' => 'Error',
                            'data' => 'Can\'t found a download url for this Modpack.'
                        ], 500);
                    }
                    if (isset($versionslist[$nb]->serverPackFileId)) {

                        $modpackserverfileid = $versionslist[$nb]->serverPackFileId;
                        $mcversion = null;
                        $loader = null;
                        foreach ($versionslist[$nb]->gameVersions as $version) {
                            if ($version !== 'Forge' && $version !== 'Fabric') {
                                $mcversion = $version;
                            } else {
                                $loader = $version;
                            }
                        }
                    } else {
                        $nb++;
                    }

                }
                $modpackdata = Http::accept('application/json')->withHeaders($headers)->get("https://api.curseforge.com/v1/mods/$modpackid/files/$modpackserverfileid")
                    ->object()->data->downloadUrl;
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $modpackdata);
                curl_setopt($ch, CURLOPT_HEADER, true);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $modpackdata = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL); // This is what you need, it will return you the last effective URL

                return response()->json([
                    'message' => 'Good',
                    'data' => $modpackdata,
                    'mcversion' => $mcversion,
                    'loader' => $loader,
                    'size' => get_headers($modpackdata, true)['Content-Length'][1],
                ], 200);
            } else if ($request->type == 'modrinth') {
                return response()->json([
                    'message' => 'Error',
                    'data' => $request->modpackid
                ], 500);
            }
        }
    }

    public function getEgg(Request $request)
    {
        $clientController = new ClientController();
        $license = $clientController->checkLicense($request->id, 327, $request->ip());
        if ($license->getStatusCode() === 200) {
            return json_decode(file_get_contents("../modpacksegg/$request->type.json"), true);
        } else {
            return $license;
        }
        ;
    }

    public function forgeDownload(Request $request)
    {
        $forgeversions = Http::get('https://files.minecraftforge.net/net/minecraftforge/forge/promotions_slim.json')->json();
        if (isset($forgeversions['promos'][$request->version . '-latest'])) {
            $forgeversion = $forgeversions['promos'][$request->version . '-latest'];
            return Redirect::to("https://maven.minecraftforge.net/net/minecraftforge/forge/$request->version-$forgeversion/forge-$request->version-$forgeversion-installer.jar");
        } else {
            return response()->json([
                'message' => 'Invalid request.'
            ], 400);
        }

    }
    public function fabricDownload(Request $request)
    {

        $fabricversion = Http::get('https://meta.fabricmc.net/v2/versions/installer')->json();
        foreach ($fabricversion as $version) {
            if ($version['stable']) {
                $versionnb = $version['version'];
                return Redirect::to("https://maven.fabricmc.net/net/fabricmc/fabric-installer/$versionnb/fabric-installer-$versionnb.jar");
            }
        }

    }
}