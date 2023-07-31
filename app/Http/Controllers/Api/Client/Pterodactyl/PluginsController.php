<?php

namespace App\Http\Controllers\Api\Client\Pterodactyl;

use App\Services\LicenseService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\License;
use App\Models\BukkitResult;
use App\Models\Versioningpluginresult;
use App\Models\PolymartResult;
use Config;
use Goutte\Client;
use Symfony\Component\HttpClient\HttpClient;
use Weidner\Goutte\GoutteFacade;
use DOMDocument;
use DOMXpath;

class PluginsController extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    private $clientController;

    public function __construct(ClientController $clientController)
    {
        $this->clientController = $clientController;
    }

    public function getBukkit(Request $request)
    {
        $licenseService = app(LicenseService::class);

        $clientController = new ClientController($licenseService);

        $license = $clientController->checkLicense($request->id , 326 , $request->ip());

        if ($license['message'] === 'done' ) {
            $url = $this->getBukkitUrl($request);
            $plugins = $this->scrapePlugins($url);

            return $plugins;
        } else {
            return $license;
        }
    }

    public function getSpigot(Request $request)
    {
        $licenseService = app(LicenseService::class);

        $clientController = new ClientController($licenseService);

        $license = $clientController->checkLicense($request->id , 326 , $request->ip());

        if ($license['message'] === 'done' ) {
            $plugins = $this->getSpigotPlugins($request);

            return $plugins;
        } else {
            return $license;
        }
    }

    public function getPolymart(Request $request)
    {
        $licenseService = app(LicenseService::class);

        $clientController = new ClientController($licenseService);

        $license = $clientController->checkLicense($request->id , 326 , $request->ip());

        if ($license['message'] === 'done' ) {
            $plugins = $this->getPolymartPlugins($request);

            return $plugins;
        } else {
            return $license;
        }
    }

    public function getCustom(Request $request)
    {
        $licenseService = app(LicenseService::class);

        $clientController = new ClientController($licenseService);

        $license = $clientController->checkLicense($request->id , 326 , $request->ip());

        if ($license['message'] === 'done' ) {
            $plugins = $this->getCustomPlugins($request);

            return $plugins;
        } else {
            return $license;
        }
    }

    public function getVersions(Request $request)
    {
        $licenseService = app(LicenseService::class);

        $clientController = new ClientController($licenseService);

        $license = $clientController->checkLicense($request->id , 326 , $request->ip());

        if ($license['message'] === 'done' ) {
            $versions = $this->getPluginVersions($request);

            return $versions;
        } else {
            return $license;
        }
    }

    public function getMcVersions(Request $request)
    {
        $licenseService = app(LicenseService::class);

        $clientController = new ClientController($licenseService);

        $license = $clientController->checkLicense($request->id , 326 , $request->ip());

        if ($license['message'] === 'done' ) {
            $versions = $this->getMinecraftVersions($request);

            return $versions;
        } else {
            return $license;
        }
    }

    public function getCategories(Request $request)
    {
        $licenseService = app(LicenseService::class);

        $clientController = new ClientController($licenseService);

        $license = $clientController->checkLicense($request->id , 326 , $request->ip());

        if ($license['message'] === 'done' ) {
            $categories = $this->getPluginCategories($request);

            return $categories;
        } else {
            return $license;
        }
    }

    public function download(Request $request)
    {
        $licenseService = app(LicenseService::class);

        $clientController = new ClientController($licenseService);

        $license = $clientController->checkLicense($request->id , 326 , $request->ip());

        if ($license['message'] === 'done' ) {
            $url = $this->getDownloadUrl($request);

            return ['url' => $url, 'success' => true];
        } else {
            return $license;
        }
    }

    private function getBukkitUrl(Request $request)
    {
        if ($request->searchFilter) {
            return "https://dev.bukkit.org/search?projects-page=$request->page?&search=$request->search";
        } elseif ($request->category) {
            return "https://dev.bukkit.org$request->category?page=$request->page&filter-game-version=$request->version";
        } else {
            return "https://dev.bukkit.org/bukkit-plugins?page=$request->page&filter-game-version=$request->version";
        }
    }

    private function scrapePlugins($url)
    {
        $pluginscrap = GoutteFacade::request('GET', $url);
        $plugins = [];

        $pluginscrap->filter("li.project-list-item")->each(function ($item) use (&$plugins) {
            $plugin = [
                'name' => $item->filter("div.details > div.info.name > div.name-wrapper.overflow-tip > a")->text(),
                'link' => $item->filter("div.details > div.info.name > div.name-wrapper.overflow-tip > a")->attr('href'),
                'icon' => $item->filter("div.avatar > a.e-avatar64 > img")->attr('src'),
                'tag' => $item->filter("div.description > p")->text(),
                'author' => $item->filter("div.details > div.info.name > span.byline > a")->text(),
                'downloadcount' => $item->filter("div.info.stats > p.e-download-count")->text(),
                'updatedate' => $item->filter("div.info.stats > p.e-update-date > abbr.tip.standard-date.standard-datetime")->text(),
                'category' => []
            ];

            $item->filter("div.categories-box > div.category-icon-wrapper > div.category-icons > a")->each(function ($item_category) use (&$plugin) {
                $plugin['category'][] = [
                    'link' => "https://deb.bukkit.org" . $item_category->attr('href'),
                    'name' => $item_category->attr('title'),
                    'img' => $item_category->filter('img')->attr('src')
                ];
            });

            $plugins[] = $plugin;
        });

        return $plugins;
    }

    private function getSpigotPlugins(Request $request)
    {
        $fields = "id,name,tag,file,testedVersions,links,external,version,author,category,rating,icon,releaseDate,updateDate,downloads,premium";
        $size = $request->size ?? 20;

        if ($request->search) {
            return Http::get("https://api.spiget.org/v2/search/resources/$request->search?size=$size&page=$request->page&sort=-downloads/resources")->json();
        }

        if ($request->version) {
            $data = Versioningpluginresult::where(['page' => $request->page, 'version' => $request->version])->first();

            if ($data && $data->updated_at->diffInHours(now()) < 24) {
                return $data->result;
            }

            $pluginslist = Http::get("https://api.spiget.org/v2/resources/for/$request->version?size=$size&page=$request->page&sort=-downloads&fields=$fields")->json();
            $finallist = [];

            foreach ($pluginslist['match'] as $plugin) {
                $id = $plugin['id'];
                $plugindetail = Http::get("https://api.spiget.org/v2/resources/$id")->json();

                $finallist[] = $plugindetail;
            }

            Versioningpluginresult::where('page', '=', $request->page)->where('version', '=', $request->version)->delete();
            Versioningpluginresult::create(['page' => $request->page, 'version' => $request->version, 'result' => $finallist]);

            return $finallist;
        } elseif ($request->category == 4) {
            return Http::get("https://api.spiget.org/v2/resources?size=$size&page=$request->page&sort=-downloads&fields=$fields")->json();
        } else {
            return Http::get("https://api.spiget.org/v2/categories/$request->category/resources?size=$size&page=$request->page&sort=-downloads&fields=$fields")->json();
        }
    }

    private function getPolymartPlugins(Request $request)
    {
        $start = $request->page * 20 - 20;

        if ($request->search) {
            return Http::get("https://api.polymart.org/v1/search?limit=20&start=$start&query=$request->search&premium=0&sort=downloads")->json()['response']['result'];
        }

        $data = PolymartResult::where('page', '=', $request->page)->first();

        if ($data && $data->updated_at->diffInHours(now()) < 24) {
            return $data['result'];
        }

        $result = Http::get("https://api.polymart.org/v1/search?limit=20&start=$start&premium=0&sort=downloads")->json();
        PolymartResult::where('page', '=', $request->page)->delete();
        PolymartResult::create(['page' => $request->page, 'result' => $result['response']['result']]);

        return $result['response']['result'];
    }

    private function getCustomPlugins(Request $request)
    {
        return Http::get("http://$request->url/pluginslist.json")->json();
    }

    private function getPluginVersions(Request $request)
    {
        if ($request->type === 'Bukkit') {
            $versionslist = GoutteFacade::request('GET', "https://dev.bukkit.org/projects/$request->pluginId/files?page=$request->page");
            $listofversions = [];

            $versionslist->filter("div.project-file-name-container > a.overflow-tip")->each(function ($node) use (&$listofversions) {
                $listofversions[] = [
                    'name' => $node->text(),
                    'downloadlink' => "https://dev.bukkit.org$node->attr('href')/download"
                ];
            });

            return $listofversions;
        } elseif ($request->type === 'PolyMart') {
            $versionslist = GoutteFacade::request('GET', "https://polymart.org/resource/$request->pluginId/updates/$request->page");
            $listofversions = [];

            $versionslist->filter("div.flex-container-centered > a.none")->each(function ($data) use (&$listofversions) {
                $listofversions[] = [
                    'name' => $data->text(),
                    'downloadid' => strstr(str_replace('setGetParameters({update: "', '', $data->attr('onclick')), '"', true)
                ];
            });

            return $listofversions;
        } else {
            return Http::get("https://api.spiget.org/v2/resources/$request->pluginId/versions?size=10&page=$request->page");
        }
    }

    private function getMinecraftVersions(Request $request)
    {
        if ($request->type === 'Bukkit') {
            $bukkitscrap = GoutteFacade::request('GET', "https://dev.bukkit.org/bukkit-plugins");
            $versions = [];

            $bukkitscrap->filter('select[id="filter-game-version"] > option')->each(function ($version) use (&$versions) {
                if ($version->attr("value") !== '') {
                    $versions[] = ['id' => $version->text()];
                }
            });

            return $versions;
        } elseif ($request->type === 'PolyMart') {
            $polymartscrap = GoutteFacade::request('GET', 'https://polymart.org/resources/plugins/all/any-version/free?sort=downloads');
            $versions = [];

            $polymartscrap->filter('div.all-small-width-only > a.category-button[rel="nofollow"]')->each(function ($version) use (&$versions) {
                if (!str_starts_with($version->attr('href'), "/resources/plugins/all") && !str_contains($version->attr('href'), "premium")) {
                    $versions[] = ['id' => $version->text()];
                }
            });

            return $versions;
        } else {
            $versions = Http::get('https://launchermeta.mojang.com/mc/game/version_manifest.json')->json();
            $releases = [];

            foreach ($versions['versions'] as $version) {
                if ($version['type'] === 'release' && (strlen($version['id']) == 4 || strlen($version['id']) == 3 || $version['id'] == '1.7.10')) {
                    $releases[] = $version;
                }
            }

            return $releases;
        }
    }

    private function getPluginCategories(Request $request)
    {
        if ($request->type === 'bukkit') {
            $bukkitscrap = GoutteFacade::request('GET', "https://dev.bukkit.org/bukkit-plugins");
            $categories = [];

            $bukkitscrap->filter('ul[id="filter-categories"] > li > a')->each(function ($category) use (&$categories) {
                $categories[] = [
                    'id' => str_replace('/bukkit-plugins?category=', '', $category->attr("href")),
                    'name' => $category->text()
                ];
            });

            return $categories;
        } elseif ($request->type === 'polymart') {
            $polymartscrap = GoutteFacade::request('GET', 'https://polymart.org/resources/plugins/all/any-version/free?sort=downloads');
            $categories = [];

            $polymartscrap->filter('div.all-small-width-only > a.category-button[rel="nofollow"]')->each(function ($category) use (&$categories) {
                if (!str_starts_with($category->attr('href'), "/resources/plugins/all") && !str_contains($category->attr('href'), "premium")) {
                    $categories[] = [
                        'id' => $category->attr('href'),
                        'name' => $category->text()
                    ];
                }
            });

            return $categories;
        } else {
            return [];
        }
    }

    private function getDownloadUrl(Request $request)
    {
        if ($request->url) {
            if(str_starts_with($request->url, 'polymart')) {
                $url = explode('-', $request->url)[1];
                $url = Http::post("https://api.polymart.org/v1/getDownloadURL?", ['resource_id' => $url ])->json()['response']['result']['url'];

            } else {
                $url = $this->get_final_location($request->url);
            }
            return $url;
        }
        return response()->json([
            'message' => 'Bad request.'
        ], 400);
    }
}
