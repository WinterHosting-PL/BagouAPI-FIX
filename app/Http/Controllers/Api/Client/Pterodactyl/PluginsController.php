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
            if( $user['banned']) {

    return response()->json([
                    'message' => 'User blacklisted.'
                ], 418);
            } else {
                if(326 !== $response['resource_id']) {
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


       
                if(326 !== $addon->name) {
                    return response()->json([
                        'message' => 'Not the good addon.'
                    ], 400);
                }
                return response()->json([
                    'message' => 'Good.'
                ], 200);;
            
        }
    }
    public function getBukkit(Request $request) {
        $license = $this->checkLicense($request);
        if($license->getStatusCode() === 200) {
            if ($request->searchFilter) {
                $url = "https://dev.bukkit.org/search?projects-page=$request->page?&search=$request->search";
            } else if ($request->category) {
                $url = "https://dev.bukkit.org$request->category?page=$request->page&filter-game-version=$request->version";
            } else {
                $url = "https://dev.bukkit.org/bukkit-plugins?page=$request->page&filter-game-version=$request->version";
            }
            $pluginscrap = GoutteFacade::request('GET', $url); 
            //dd($versionslist);
            global $pluginslist;
            $pluginslist = array();
            $pluginscrap->filter("li.project-list-item")->each(function ($item) {
                global $name;
                global $link;
                global $file;
                global $icon;
                global $tag;
                global $author;
                global $downloadcount;
                global $updatedate;
                global $category;
                $name = ''; 
                $link = '';
                $icon = ''; 
                $tag = ''; 
                $author = '';
                $downloadcount = ''; 
                $updatedate = '';  
                $category = array();
                $item->filter("div.avatar > a.e-avatar64")->each(function ($item_link) {
                    global $link;
                    $link = $item_link->attr('href');
                    $item_link->filter('img')->each(function ($item_avatar) {
                        global $icon;
                        $icon = $item_avatar->attr('src');
                    });
                });
                $item->filter("div.details > div.info.name")->each(function ($item_basic) {
                    $item_basic->filter("div.name-wrapper.overflow-tip > a")->each(function ($item_name) {
                        global $name;
                        $name = $item_name->text();
                    });
                    $item_basic->filter("span.byline > a")->each(function ($item_author) {
                        global $author;
                        $author = $item_author->text();
                    });
                });
                $item->filter("div.description >  p")->each(function ($item_tag) {
                    global $tag;
                    $tag = $item_tag->text();
                });
                $item->filter("div.info.stats")->each(function ($item_info) {
                    global $downloadcount;
                    global $updatedate;
                    $downloadcount = $item_info->filter("p.e-download-count")->text();
                    $updatedate = $item_info->filter("p.e-update-date > abbr.tip.standard-date.standard-datetime")->text();
                });
                $item->filter("div.categories-box > div.category-icon-wrapper")->each(function ($item_categories) {
                    $item_categories->filter("div.category-icons")->each(function ($item_categorie) {
                        $item_categorie->filter("a")->each(function ($item_categorielink) {
                            global $category;
                            array_push($category, 
                                array(
                                    "link" => "https://deb.bukkit.org" . $item_categorielink->attr('href'), 
                                    "name" => $item_categorielink->attr("title"), 
                                    "img" => $item_categorielink->filter('img')->attr("src")));
                        });
                    });
                });
                global $pluginslist;
                $downloadlink = $link . "/files/latest";
                array_push($pluginslist, array(
                'name' => $name, 
                'links' => array('discussion' => $link), 
                'file' => array('type' => '.jar'), 
                'icon' => array('url' => $icon),
                'tag' => $tag,
                'author' => $author,
                'downloadcount' => $downloadcount,
                'downloadlink' => $downloadlink,
                'updatedate' => $updatedate,
                'category' => $category
                ));
            });
            
            return $pluginslist;
        } else {
            return $license;
        };
    }
    public function getSpigot(Request $request) {
        $license = $this->checkLicense($request);
        $fields = "id,name,tag,file,testedVersions,links,external,version,author,category,rating,icon,releaseDate,updateDate,downloads,premium";
        $size = 20;
        if($request->size) {
            $size = $request->size;
        }
        if($license->getStatusCode() === 200) {
            if($request->search) {
                return Http::get("https://api.spiget.org/v2/search/resources/$request->search?size=$size&page=$request->page&sort=-downloads/resources")->json();
            }
            if($request->version) {
                $data = Versioningpluginresult::where(['page' => $request->page, 'version' => $request->version])->first();
                if($data) {
                    if($data->updated_at->diffInHours(now())<24) {
                        return $data->result;
                    }
                }
                $pluginslist = Http::get("https://api.spiget.org/v2/resources/for/$request->version?size=$size&page=$request->page&sort=-downloads&fields=$fields")->json();
                $finallist = array();
                foreach($pluginslist['match'] as $plugin) {
                    $id = $plugin['id'];
                    $plugindetail = Http::get("https://api.spiget.org/v2/resources/$id")->json();
                    
                    array_push($finallist, $plugindetail);
                }
                Versioningpluginresult::where('page', '=', $request->page)->where('version', '=', $request->version)->delete();
                Versioningpluginresult::create(['page' => $request->page, 'version' => $request->version, 'result' => $finallist]);
                return $finallist;

            }
            else if($request->category == 4) {
                return Http::get("https://api.spiget.org/v2/resources?size=$size&page=$request->page&sort=-downloads&fields=$fields")->json();
            } else {
                return Http::get("https://api.spiget.org/v2/categories/$request->category/resources?size=$size&page=$request->page&sort=-downloads&fields=$fields")->json();
            }
        } else {
            return $license;
        };
    }
    public function getPolymart(Request $request) {
        $license = $this->checkLicense($request);
        $start = $request->page*20-20;

        if($license->getStatusCode() === 200) {
            if($request->search) {
                return Http::get("https://api.polymart.org/v1/search?limit=20&start=$start&query=$request->search&premium=0&sort=downloads")->json();
            }
            $data = PolymartResult::where('page', '=', $request->page)->first();
            if($data) {
                if($data->updated_at->diffInHours(now())<24) {
                    return $data['result'];
                }

            }
            $result = Http::get("https://api.polymart.org/v1/search?limit=20&start=$start&premium=0&sort=downloads")->json();
            PolymartResult::where('page', '=', $request->page)->delete();
            PolymartResult::create(['page' => $request->page, 'result' => $result['response']['result']]);
            return $result['response']['result'];
            /*if($request->category == null && $request->version == null) {
                $pluginscrap = GoutteFacade::request('GET', "https://polymart.org/resources/plugins/all/any-version/free?sort=downloads");

            } else if ($request->category) {
                $pluginscrap = GoutteFacade::request('GET', "https://polymart.org/resources/plugins/$request->category/any-version/free?sort=downloads");
            } else if ($request->version) {
                $pluginscrap = GoutteFacade::request('GET', "https://polymart.org/resources/plugins/all/$request->version/free?sort=downloads");
            } else {
                $pluginscrap = GoutteFacade::request('GET', "https://polymart.org/resources/plugins/$request->category/$request->version/free?sort=downloads");
            }
            global $pluginslist;
            $pluginslist = array();
            $pluginscrap->filter('div[id="resource-grid"]')->each(function ($item) {
                    global $name;
                    global $link;
                    global $file;
                    global $icon;
                    global $tag;
                    global $author;
                    global $downloadcount;
                    global $review;
                    $name = ''; 
                    $link = '';
                    $icon = ''; 
                    $tag = ''; 
                    $author = '';
                    $downloadcount = ''; 
                    $review = '';  
                    $item->filter("a.product-title")->each(function ($item_info) {
                        global $name;
                        $name = $item_info->text();
                    });
                    $item->filter("div > div > div > a.none")->each(function ($item_info) {
                        global $link;
                        $link = "https://polymart.org/" . $item_info->attr('href');
                    });
                    $item->filter("div > a > div > object > img")->each(function ($item_info) {
                        global $icon;
                        $icon = $item_info->attr('src');
                    });
                    $item->filter("div.product-subtitle")->each(function ($item_info) {
                        global $tag;
                        $expStr=explode("&#x2014;",$item_info->text());
                        $tag = $expStr[1];
                    });
                    $item->filter("div.product-subtitle > a")->each(function ($item_info) {
                        global $author;
                        $author = $item_info->text();
                    });
                    $item->filter("span")->each(function ($item_info) {
                        if(!$item->filter("span.fa.fa-download")) {
                            return;
                        }
                        global $downloadcount;
                        $downloadcount = $item_info->text();
                    });
                    $item->filter("span.fa.fa-star")->each(function ($item_info) {
                        if(!$item_info->attr('style'))
                        global $author;
                        $author = $item_info->text();
                    });
            });
            dd("https://polymart.org/resources/plugins/$request->category/$request->version/free?sort=downloads");*/
        } else {
            return $license;
        };
    }
    public function getCustom(Request $request) {
        $license = $this->checkLicense($request);

        if($license->getStatusCode() === 200) {
            return Http::get("http://$request->url/pluginslist.json")->json();
        } else {
            return $license;
    }
    }
    public function getVersions(Request $request) {
        $license = $this->checkLicense($request);

        if($license->getStatusCode() === 200) {
            if($request->type === 'Bukkit') {
                $versionslist = GoutteFacade::request('GET', "https://dev.bukkit.org/projects/$request->pluginId/files?page=$request->page");
                global $listofversions;
                $listofversions = array();
                $versionslist->filter("div.project-file-name-container > a.overflow-tip")->each(function ($node) {
                    $download = $node->attr('href');
                    global $listofversions;
                    array_push($listofversions, array('name' => $node->text(), 'downloadlink' => "https://dev.bukkit.org$download/download"));
                });
                
                return $listofversions;
                
            } else if($request->type === 'PolyMart') {
                $versionslist = GoutteFacade::request('GET', "https://polymart.org/resource/$request->pluginId/updates/$request->page");
                global $listofversions;
                $listofversions = array();
                $versionslist->filter("div.flex-container-centered")->each(function ($node) {
                    if(!str_starts_with($node->attr('id'), 'update')) {
                        return;
                    }
                    $node->filter("a.none")->each(function ($data) {
                        global $listofversions;
                        array_push($listofversions, array('name' => $data->text(), 'downloadid' => strstr(str_replace('setGetParameters({update: "', '', $data->attr('onclick')), '"', true)));
                    });
                });
                return $listofversions;
            } else {
                return Http::get("https://api.spiget.org/v2/resources/$request->pluginId/versions?size=10&page=$request->page");
            }
        } else {
            return $license;
        }
        
        
    }
    public function getMcVersions(Request $request) {
        $license = $this->checkLicense($request);
        if($license->getStatusCode() === 200) {
            if($request->type === 'Bukkit') {
                $bukkitscrap = GoutteFacade::request('GET', "https://dev.bukkit.org/bukkit-plugins"); 
                global $versions;
                $versions = array();
                $bukkitscrap->filter('select[id="filter-game-version"] > option')->each(function ($version) {
                    if($version->attr("value") !== '') {
                        global $versions;
                        str_replace(' ', '', $version->text());
                        array_push($versions, array('id' => $version->text()));
                    }
                    
                });
                return $versions;

            } else if($request->type === 'PolyMart') {
                $polymartscrap = GoutteFacade::request('GET', 'https://polymart.org/resources/plugins/all/any-version/free?sort=downloads');
                global $versions;
                $versions = array();
                $polymartscrap->filter('div.all-small-width-only > a.category-button[rel="nofollow"]')->each(function ($version) {
                        if(str_starts_with($version->attr('href'), "/resources/plugins/all") && !str_contains($version->attr('href'), "premium")) {
                            global $versions;
                            array_push($versions, array('id' => $version->text()));
                        }

                
                    
                });
                return array(array('id' => -1), array('id' => -1));
                return $versions;
            }
            $versions = Http::get('https://launchermeta.mojang.com/mc/game/version_manifest.json')->json();
            $releases = array();
            foreach($versions['versions'] as $version) {
                if($version['type'] === 'release' && (strlen($version['id']) == 4 || strlen($version['id']) == 3 || $version['id'] == '1.7.10')) {
                array_push($releases, $version);
                }
            }
            return $releases;
    } else {
        return $license;
    };
    }
    public function getCategories(Request $request) {
        $license = $this->checkLicense($request);
        if($license->getStatusCode() === 200) {
            if($request->type === 'bukkit') {
                $bukkitscrap = GoutteFacade::request('GET', "https://dev.bukkit.org/bukkit-plugins"); 
                global $categories;
                $categories = array();
                $bukkitscrap->filter('ul.listing.listing-game-category.game-category-listing > li.tier-holder > ul.categories-tier.indent-0 > li.level-categories-nav > a')->each(function ($category) {
                    global $categories;
                    array_push($categories, array('name' => $category->filter('span')->text(), 'id' => $category->attr('href')));
                });
                return $categories;

            } else if ($request->type === 'spigot') {
                return Http::get('https://api.spiget.org/v2/categories')->json();
            } else if ($request->type === 'polymart') {
                $polymartscrap = GoutteFacade::request('GET', 'https://polymart.org/resources/plugins/all/any-version/free?sort=downloads');
                global $categories;
                $categories = array();
                $polymartscrap->filter('div.all-small-width-only > a.category-button.theme-color[rel="nofollow"][style="font-size: 14px; padding: 4px 12px; margin: 4px;"]')->each(function ($category) {
                        if(!str_starts_with($category->attr('href'), "/resources/plugins/all") && !str_contains($category->attr('href'), "premium") && $category->text() !== 'All') {
                            global $categories;
                            array_push($categories, array('name' => $category->text(), 'value' => strstr(explode("/resources/plugins/", $category->attr('href'))[1], '/', true)));
                        }
                        

                        
                    
                });
                return $categories;
            }
            return response()->json([
                'message' => 'Bad request.'
            ], 400);
    } else {
        return $license;
    };
    }
    public function Download(Request $request) {
        $license = $this->checkLicense($request);
        if($license->getStatusCode() === 200) {
            if ($request->url) {
                if(str_starts_with($request->url, 'polymart')) {
                    $url = explode('-', $request->url)[1];
                    $url = Http::post("https://api.polymart.org/v1/getDownloadURL?", ['resource_id' => $url ])->json()['response']['result']['url'];

                } else {
                    $url = $this->get_final_location($request->url);
                }
                return array('url' => $url, 'success' => true);
            }
            return response()->json([
                'message' => 'Bad request.'
            ], 400);
    } else {
        return $license;
    };
    }
    function get_final_location($url, $index=null) {

        if (is_array($url)) {
            $headers = $url;
        }
        else {
            $headers = get_headers($url, 1)['Location'];    
            if (count($headers) == 0) {
                return $url;
            }
        }
    
        if (is_null($index)) {
            $to_check   = end($headers);
            $index      = count($headers) - 1;
        }
        else {
            $to_check = $headers[$index];
        }
    
        if (!filter_var($to_check, FILTER_VALIDATE_URL) === false) {
            if (count($headers) - 1 > $index) {
                $lp = parse_url($headers[$index], PHP_URL_SCHEME) . "://" . parse_url($headers[$index], PHP_URL_HOST) . $headers[$index+1];
            }
            else {
                $lp = $to_check;
            }
        }
        else {
            $index--;
            $lp = landingpage($headers, $index);
        }
    
        return $lp;
    
    }
}
