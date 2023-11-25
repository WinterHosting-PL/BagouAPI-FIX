<?php

namespace App\Http\Controllers\Api\Client\Web;

use App\Mail\TestMail;
use App\Models\License;
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
        $addons = Products::where('hide', false);
        if($request->search !== '' && $request->search) {
            $addons->where('name', 'like', "%$request->search%")->orWhere('description', 'like', "%$request->search%")->orWhere('tag', 'like', "%$request->search%");
        }
        if($request->category !== '' && $request->category) {
            $addons->where('category', 'like', "%$request->category%");
        }
        $addons = $addons->select('Id', 'name', 'new', 'tag', 'slug', 'category', 'price', 'icon')->get();
        $page = ceil(count($addons)/$request->perpage);

        $addons = array_slice($addons->toArray(), $request->page*$request->perpage-$request->perpage, $request->perpage );

        return ['message' => 'success', 'totalpage' => $page, 'data' => $addons];
    }
    public function getone(Request $request) {
        /*
        * This function return a addon with their description.
        * She use "id" parameters.
        */
        $user = auth('sanctum')->user();
        $product = Products::where('slug', '=', $request->id)->first();
        if(!$product) {
            return ['status' => 'error', 'message' => 'No product found'];
        }
        if($product->hide) {
            return ['status' => 'error', 'message' => 'No product found'];
        }
        if (!$user) {
            return ['status' => 'success', 'data' => $product, 'owned' => false];
        }
        return ['status' => 'success', 'data' => $product, 'owned' => License::where('product_id', $product->id)->where('user_id', $user->id)->exists()];
    }

}

