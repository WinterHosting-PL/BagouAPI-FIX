<?php

namespace App\Http\Controllers\Api\Client\Web;

use App\Mail\TestMail;
use App\Notifications\TestNotification;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\Products;
use Config;
class AddonsController extends BaseController
{
    public function get(Request $request) {
        /*Http::post
        * This function return a list of addons with their description.
        * She use "search, perpage, page" parameters.
        */
        if(!$request->perpage || $request->perpage == 0 ) {
            $request->perpage = 10;
        }
        $addons = Products::get();
        if($request->search !== '' && $request->search) {
            $addons =  Products::where('name', 'like', "%$request->search%")->get();
        }
        $page = ceil(count($addons)/$request->perpage);

        $addons = array_slice($addons->toArray(), $request->page*$request->perpage-$request->perpage, $request->perpage );

        return ['message' => 'success', 'totalpage' => $page, 'data' => $addons];
    }
    public function getone(Request $request) {
        /*
        * This function return a addon with their description.
        * She use "id" parameters.
        */

        return ['message' => 'success', 'data' => Products::where('id', '=', $request->id)->firstOrFail()];
    }

}

