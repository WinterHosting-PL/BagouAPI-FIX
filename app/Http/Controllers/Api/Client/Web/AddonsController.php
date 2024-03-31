<?php

namespace App\Http\Controllers\Api\Client\Web;

use App\Mail\TestMail;
use App\Models\License;
use App\Models\ProductsDescription;
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
        $language = $request->lang ?? 'en';
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
        $addons = $addons->select('id', 'name', 'new', 'tag', 'slug', 'category', 'price', 'icon')
            ->with(['descriptions' => function ($query) use ($language) {
                $query->where('language', $language);
            }])
            ->paginate($request->perpage);

        foreach ($addons as $addon) {
            $description = $addon->descriptions ? $addon->descriptions->first() : null;
            if ($description && $description->tag) {
                $addon->tag = $description->tag;
            }
            unset($addon->descriptions);
        }
        return response()->json([
            'message' => 'success',
            'data' => $addons->items(),
            'last_page' => $addons->lastPage(),
            'current_page' => $addons->currentPage(),
        ]);
    }
    public function getone(Request $request) {
        /*
        * This function return a addon with their description.
        * She use "id" parameters.
        */
        $user = auth('sanctum')->user();
        $language = $request->lang ?? 'en';
        $product = Products::with(['descriptions' => function ($query) use ($language) {
            $query->where('language', $language);
        }])->where('slug', '=', $request->id)->first();
        if(!$product || $product->hide) {
            return response()->json(['status' => 'error', 'message' => 'No product found'], 404);
        }
        $description = $product->descriptions ? $product->descriptions->first() : null;
        if ($description instanceof ProductsDescription && $description->tag) {
            $product->tag = $description->tag;
            $product->description = $description->description;
        }
        unset($product->descriptions);
        if (!$user) {
            return response()->json(['status' => 'success', 'data' => $product, 'owned' => false]);
        }
        return response()->json(['status' => 'success', 'data' => $product, 'owned' => $product->isOwnedBy($user)]);
    }

}

