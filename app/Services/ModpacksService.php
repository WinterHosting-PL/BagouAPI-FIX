<?php

namespace App\Services;

use App\Models\ModPacksResult;
use Illuminate\Support\Facades\Http;
use Weidner\Goutte\GoutteFacade;
use App\Models\ModPacksDownloadLinks;

class ModpacksService
{
    private $curseKey;

    public function __construct()
    {
        $this->curseKey = config('api.cursekey');
    }

    public function cachedModpack(string $page ,string $type)
    {
        $data = ModPacksResult::where(['page' => $page ,'type' => $type])->first();
        if ( $data->updated_at->diffInHours(now()) < 24 ) {
            return json_decode($data->result ,true);
        }
        return false;
    }
    public function addCached(string $type, string $page, array $modpacks) {
ModPacksResult::where('page', '=', $page)->where('type', '=', $type)->delete();
                ModPacksResult::create(['page' => $page, 'result' => json_encode($modpacks), 'type' => $type]);
    }
    public function getModpacksCurse(string $game_versions ,string $page ,string $search ,string $loaders)
    {
        $cursekey = $this->curseKey;
        $headers = ['User-Agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/92.0.4515.81 Safari/537.36' ,'x-api-key' => $cursekey];
        if ( $game_versions ) {
            return Http::accept('application/json')
                ->withHeaders($headers)
                ->get('https://api.curseforge.com/v1/mods/search' ,[
                    'index' => $page * 20 - 20 ,
                    'pageSize' => 20 ,
                    'gameId' => 432 ,
                    'classId' => 4471 ,
                    'searchFilter' => $search ,
                    'sortField' => 2 ,
                    'sortOrder' => 'desc' ,
                    'gameVersion' => $game_versions
                ])->object()->data;
        } else if ( !$game_versions && !$loaders ) {
            return Http::accept('application/json')
                ->withHeaders($headers)
                ->get('https://api.curseforge.com/v1/mods/search' ,[
                    'index' => $page * 20 - 20 ,
                    'pageSize' => 20 ,'gameId' => 432 ,
                    'classId' => 4471 ,
                    'searchFilter' => $search ,
                    'sortField' => 2 ,
                    'sortOrder' => 'desc'
                ])->object()->data;
        }
        return [];
    }

    public function getModpacksModrinth(string $game_versions ,string $page ,string $search ,string $loaders)
    {
        $headers = ['User-Agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/92.0.4515.81 Safari/537.36'];
        if ( $game_versions ) {
            return Http::accept('application/json')->withHeaders($headers)
                ->get('https://api.modrinth.com/v2/search' ,[
                    'offset' => $page * 20 - 20 ,
                    'limit' => 20 ,'query' => $search ,
                    'facets' => "[[\"versions:$game_versions\"],[\"project_type:modpack\"]]"])
                ->object()->hits;
        } else if ( $loaders ) {
            return Http::accept('application/json')->withHeaders($headers)
                ->get('https://api.modrinth.com/v2/search' ,[
                    'offset' => $page * 20 - 20 ,
                    'limit' => 20 ,'query' => $search ,
                    'facets' => "[[\"categories:$loaders\"],[\"project_type:modpack\"]]"
                ])->object()->hits;
        } else {
            return Http::accept('application/json')->withHeaders($headers)
                ->get('https://api.modrinth.com/v2/search' ,[
                    'offset' => $page * 20 - 20 ,
                    'limit' => 20 ,'query' => $search ,
                    'facets' => "[[\"project_type:modpack\"]]"
                ])->object()->hits;
        }
    }    private function createHttpClient()
    {
        return Http::accept('application/json')->withHeaders([
            'User-Agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/92.0.4515.81 Safari/537.36'
        ]);
    }


     public function getModpacksFtb(string $game_versions, string $page, string $search, string $loaders)
{
    $httpClient = $this->createHttpClient();

    if ($search) {
        $tmplist = $httpClient->get("https://api.modpacks.ch/public/modpack/search/250?term=$search")->object()->hits;
    } else {
        $tmplist = $httpClient->get('https://api.modpacks.ch/public/modpack/popular/installs/250')->object();
    }

    $tmplist = array_slice($tmplist->packs, $page * 20 - 20, 20);
    $modpackslist = [];
    foreach ($tmplist as $modpack) {
        $modpackget = $httpClient->get("https://api.modpacks.ch/public/modpack/$modpack")->object();

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
            $modpackslist[] = [
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
            ];
        }
    }
    if ($loaders && $loaders !== '') {
        $tmplist = array();
        foreach ($modpackslist as $modpack) {
            if (strpos($modpack['loader'], $loaders) !== false) {
                array_push($tmplist, $modpack);
            };
        };
        return $tmplist;
    }
    if ($game_versions && $game_versions !== '') {
        $tmplist = array();
        foreach ($modpackslist as $modpack) {
            if (in_array($game_versions, $modpack['versions']) !== false) {
                array_push($tmplist, $modpack);
            };
        };
        return $tmplist;
    }
    return [];
}


public function getModpacksTechnicpack(string $game_versions, string $page, string $search, string $loaders)
{
    $url = "https://www.technicpack.net/modpacks/sort-by/popular?&q=$search&page=$page";
    $modpacksscrap = GoutteFacade::request('GET', $url);

    $modpackslist = [];
    $tmpmodpacks = [];
    $lastbuild = Http::get('http://api.technicpack.net/launcher/version/stable4')->object()->build;

    $modpacksscrap->filter("div.modpack-item")->each(function ($item) use (&$tmpmodpacks, $lastbuild) {
        $link = '';
        $downloadcount = '';

        $item->filter("div.modpack-image > a")->each(function ($item_link) use (&$link) {
            $link = $item_link->attr('href');
        });

        $item->filter("div.modpack-stats > span.stat.downloads")->each(function ($item_downloads) use (&$downloadcount) {
            $downloadcount = $item_downloads->text();
        });

        $tokens = explode('/', $link);
        $str = trim(end($tokens));
        $tokens = explode('.', $str);
        $str = $tokens[0];
        $downloadlink = "https://api.technicpack.net/modpack/" . $str . "?build=$lastbuild";

        $tmpmodpacks[] = [
            'link' => $link,
            'downloadcount' => $downloadcount,
            'downloadlink' => $downloadlink,
        ];
    });

    foreach ($tmpmodpacks as $modpack) {
        $modpacksscrap = GoutteFacade::request('GET', $modpack['link']);

        $name = '';
        $description = '';
        $versions = '';
        $author = '';
        $icon = '';

        $modpacksscrap->filter("div.modpack-title")->each(function ($item) use (&$name, &$icon) {
            $item->filter("h1")->each(function ($item_name) use (&$name, &$icon) {
                $name = $item_name->text();
                $icon = $item_name->filter("img")->attr('src');
            });
        });

        $modpacksscrap->filter("div.modpack-meta")->each(function ($item_versions) use (&$versions, &$author) {
            $versions = explode(' using ', explode('Minecraft ', $item_versions->text())[1])[0];
            $author = $item_versions->filter("a")->first()->text();
        });

        $modpacksscrap->filter('div.modpack-overview-content')->each(function ($item) use (&$description) {
            $description = $item->html();
        });

        $downloadlink = ModPacksDownloadLinks::where('link', '=', $modpack['link'])->where('type', '=', 'technicpack')->first();
        if ($downloadlink) {
            $modpack['downloadlink'] = $downloadlink->downloadlink;
        }

        if (str_starts_with($modpack['downloadlink'], 'https://api.technicpack.net')) {
            $data = Http::get($modpack['downloadlink'])->object();
            if (isset($data->serverPackUrl) && $data->serverPackUrl !== null) {
                $modpack['downloadlink'] = $data->serverPackUrl;
            }
        }

        if (!str_starts_with($modpack['downloadlink'], 'https://api.technicpack.net')) {
            $modpackslist[] = [
                'name' => $name,
                'versions' => [$versions],
                'author' => $author,
                'link' => $modpack['link'],
                'description' => $description,
                'logo' => ['thumbnailUrl' => $icon],
                'downloadcount' => $modpack['downloadcount'],
                'downloadlink' => $modpack['downloadlink'],
            ];
        }
    }

    return $modpackslist;
}
public function getModpacksVoidsWrath(string $game_versions, string $page, string $search, string $loaders)
{
    $url = "https://voidswrath.com/mod-packs/";
    $modpacksscrap = GoutteFacade::request('GET', $url);

    $tmppack = [];
    $modpacksscrap->filter("div.mod-packs > ul > li > a")->each(function ($item) use (&$tmppack, $search) {
        $name = '';
        $versions = '';
        $icon = '';
        $link = $item->attr('href');

        $item->filter("div.mod-pack-thumb")->each(function ($item_icon) use (&$icon) {
            $icon = explode("')", explode("image:url('", $item_icon->attr('style'))[1])[0];
            $item_icon->filter("div.mod-pack-title-list")->each(function ($item_name) use (&$name) {
                $name = $item_name->text();
            });
            $item_icon->filter("div.mod-pack-list-details > ul > li")->each(function ($item_versions) use (&$versions) {
                if (str_contains($item_versions->text(), 'Minecraft')) {
                    $versions = explode(': ', $item_versions->text())[1];
                }
            });
        });

        if (str_contains(strtolower($name), $search)) {
            $modpacksscrap = GoutteFacade::request('GET', $link);
            $downloadlink = '';
            $description = '';

            $modpacksscrap->filter("a.more-details-installer")->each(function ($item) use (&$downloadlink) {
                if ($item->text() == 'Download the Server Pack') {
                    $downloadlink = $item->attr('href');
                }
            });

            $modpacksscrap->filter("div.post-server-content")->each(function ($item) use (&$description) {
                $description = $item->html();
            });

            $tmppack[] = [
                'name' => $name,
                'description' => $description,
                'versions' => $versions,
                'logo' => ['thumbnailUrl' => $icon],
                'link' => $link,
                'downloadlink' => $downloadlink,
            ];
        }
    });

    return $tmppack;
}
 public function getCurseForgeModpackDescription($modpackId)
    {
        $headers = ['User-Agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/92.0.4515.81 Safari/537.36', 'x-api-key' => $this->curseKey];
        $description = Http::accept('application/json')->withHeaders($headers)->get("https://api.curseforge.com/v1/mods/$modpackId/description")
            ->object()->data;

        return $description;
    }

    public function getModrinthModpackDescription($modpackId)
    {
        $description = Http::accept('application/json')->get("https://api.modrinth.com/v2/project/$modpackId")
            ->object()->body;

        $regexImages = '~https?://\S+?(?:png|gif|jpe?g)~';
        $regexLinks = '~(?<!src=\')https?://\S+\b~x';

        $description = preg_replace($regexImages, "<img src='\\0'>", $description);
        $description = preg_replace($regexLinks, "<a href='\\0'>\\0</a>", $description);
        $description = nl2br($description);

        return $description;
    }
}