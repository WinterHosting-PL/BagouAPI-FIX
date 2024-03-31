<?php

namespace App\Http\Controllers\Api\Client\Web\Admin\Products;

use App\Http\Controllers\Controller;
use App\Mail\ProductUpdateMail;
use App\Models\Orders;
use App\Models\Products;
use App\Models\ProductsDescription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use XMLWriter;

class ProductsController extends Controller
{
    /**
     * Liste des produits.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function listProducts(Request $request)
    {
        $user = auth('sanctum')->user();

        if (!$user || $user->role !== 1) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }
        $page = $request->input('page', 1);
        $perPage = $request->input('perpage', 10);
        $search = $request->input('search', '');
        $productsQuery = Products::query();

        if ($search) {
            $productsQuery->where(function ($query) use ($search) {
                $query->where('name', 'LIKE', '%' . $search . '%')
                    ->orWhere('description', 'LIKE', '%' . $search . '%');
            });
        }
        $total = ceil($productsQuery->count()/$perPage);
        $products = $productsQuery->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->with('descriptions')
            ->get();

        return response()->json(['status' => 'success', 'data' => $products, 'total' => $total]);
    }

    /**
     * Créer un produit.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function createProduct(Request $request)
    {
        $this->validate($request, [
            'name' => 'required',
            'version' => 'required',
            'price' => 'required|numeric',
            'sxcname' => 'required',
            'bbb_id' => 'required',
            'link' => 'required',
            'licensed' => 'required|boolean',
            'isnew' => 'required|boolean',
            'autoinstaller' => 'required|boolean',
            'recurrent' => 'required|boolean',
            'tab' => 'required|boolean',
            'slug' => 'required',
            'category' => 'required',
            'isWings' => 'required|boolean',
            'tabroute' => 'nullable',
            'logo' => 'required|file|mimes:webp',
            'zip' => 'required|file|mimes:zip',
            'extension' => 'required|boolean',
            'extension_product' => 'nullable',
            'descriptions' => 'required|array',
            'descriptions.*.language' => 'required|string',
            'descriptions.*.description' => 'required|string',
            'descriptions.*.tag' => 'required|string',
            'hide' => 'required|boolean'
        ]);

        $user = auth('sanctum')->user();
        if (!$user || $user->role !== 1) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        $productName = $request->input('name');
        $productTag = '';
        $productDescriptions = $request->input('descriptions');
        $productisWings = $request->input('isWings');
        $productSlug = $request->input('slug');
        $productCat = $request->input('category');
        $productVersion = $request->input('version');
        $productPrice = $request->input('price');
        $productSxcName = $request->input('sxcname');
        $productBBBId = $request->input('bbb_id');
        $productLink = json_decode($request->input('link'), true);
        $productLicensed = $request->input('licensed');
        $productNew = $request->input('isnew');
        $productAutoInstaller = $request->input('autoinstaller');
        $productRecurrent = $request->input('recurrent');
        $productTab = $request->input('tab');
        $productTabRoute = $request->input('tabroute') ? $request->input('tabroute') : '';
        $productDescription = '';
        $productHide = $request->input('hide');
        $productExtension = $request->input('extension');
        $productExtensionProduct = $request->input('extension_product');

        // Créer le produit sur Stripe
        $response = Http::asForm()->withHeaders([
            'Authorization' => 'Bearer ' . config('services.stripe.secret'),
            'Content-Type' => 'application/x-www-form-urlencoded',
        ])->post('https://api.stripe.com/v1/products', [
            'name' => $productName,
        ]);

        if ($response->failed()) {
            return response()->json(['status' => 'error', 'message' => 'Failed to create product on Stripe'], 500);
        }

        $stripeProduct = $response->json();
        $stripeProductId = $stripeProduct['id'];
        if($productPrice == 0) {
            $productPrice +=1;
        }

        // Créer le prix du produit sur Stripe
        $response = Http::asForm()->withHeaders([
            'Authorization' => 'Bearer ' . config('services.stripe.secret'),
            'Content-Type' => 'application/x-www-form-urlencoded',
        ])->post('https://api.stripe.com/v1/prices', [
            'product' => $stripeProductId,
            'unit_amount' => $productPrice * 100, // Le prix est en centimes
            'currency' => 'eur',
        ]);

        if ($response->failed()) {
            return response()->json(['status' => 'error', 'message' => 'Failed to create product price on Stripe'], 500);
        }

        $stripePrice = $response->json();
        $stripePriceId = $stripePrice['id'];
        $newId = Products::max('id') +1;

        $logoFileName = $newId . '.webp';
        $logoPath = $request->file('logo')->storeAs('public/logos', $logoFileName);
        $logoUrl = Storage::url($logoPath);
        $zipFileName = $newId . '.zip';
        $request->file('zip')->storeAs('private/zips', $zipFileName);
        $product = Products::create([
            'name' => $productName,
            'tag' => $productTag,
            'version' => $productVersion,
            'price' => $productPrice,
            'sxcname' => $productSxcName,
            'bbb_id' => $productBBBId,
            'link' => $productLink,
            'licensed' => $productLicensed,
            'new' => $productNew,
            'isWings' => $pproductisWings,
            'autoinstaller' => $productAutoInstaller,
            'recurrent' => $productRecurrent,
            'tab' => $productTab,
            'tabroute' => $productTabRoute,
            'description' => $productDescription,
            'stripe_id' => $stripeProductId,
            'stripe_price_id' => $stripePriceId,
            'icon' => $logoUrl,
            'hide' => $productHide,
            'extension' => $productExtension,
            'slug' => $productSlug,
            'category' => $productCat,
            'extension_product' => $productExtensionProduct
        ]);
        foreach($productDescriptions as $description) {
            ProductsDescription::create([
                'product_id' => $product->id,
                'description' => $description->description,
                'language' => $description->language,
                'tag' => $description->tag
            ]);
        }
        $product->load('descriptions');
        return response()->json(['status' => 'success', 'message' => 'done', 'data' => $product], 202);
    }

    /**
     * Modifier un produit.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function updateProduct(Request $request, $id)
    {
        $this->validate($request, [
            'name' => 'required',
            'slug' => 'required',
            'category' => 'required',
            'version' => 'required',
            'price' => 'required|numeric',
            'isWings' => 'required|boolean',
            'sxcname' => 'required',
            'bbb_id' => 'required',
            'link' => 'required',
            'licensed' => 'required|boolean',
            'isnew' => 'required|boolean',
            'autoinstaller' => 'required|boolean',
            'recurrent' => 'required|boolean',
            'tab' => 'required|boolean',
            'tabroute' => 'nullable',
            'descriptions' => 'required|array',
            'descriptions.*.language' => 'required|string',
            'descriptions.*.description' => 'required|string',
            'descriptions.*.tag' => 'required|string',
            'logo' => 'nullable|file|mimes:webp',
            'zip' => 'nullable|file|mimes:zip',
            'extension' => 'required|boolean',
            'extension_product' => 'nullable',
            'hide' => 'required|boolean'
        ]);
        $user = auth('sanctum')->user();
        if (!$user || $user->role !== 1) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        $product = Products::find($id);

        if (!$product) {
            return response()->json(['status' => 'error', 'message' => 'Product not found'], 404);
        }
        if ($request->hasFile('logo')) {
            $logoFileName = $product->id . '.webp';
            if (Storage::exists('public/logos/' . $logoFileName)) {
                Storage::delete('public/logos/' . $logoFileName);
            }
            $logoPath = $request->file('logo')->storeAs('public/logos', $logoFileName);
            $logoUrl = Storage::url($logoPath);
            $product->icon = $logoUrl;
        }
        if ($request->hasFile('zip')) {
            $zipFileName = $product->id. '.zip';
            if (Storage::exists('private/zips/' . $logoFileName)) {
                Storage::delete('private/zips/' . $logoFileName);
            }
            $request->file('zip')->storeAs('private/zips', $zipFileName);
        }

        if(floatval($request->input('price')) !== $product->price) {
            $response = Http::asForm()->withHeaders([
                'Authorization' => 'Bearer ' . config('services.stripe.secret'),
                'Content-Type' => 'application/x-www-form-urlencoded',
            ])->post('https://api.stripe.com/v1/prices', [
                'product' => $product->stripe_id,
                'unit_amount' => $request->input('price') * 100, // Le prix est en centimes
                'currency' => 'eur',
            ]);

            if ($response->failed()) {

                return response()->json(['status' => 'error', 'message' => 'Failed to create product price on Stripe', 'data' => print_r($response)], 500);
            }

            $stripePrice = $response->json();
            $stripePriceId = $stripePrice['id'];
            $product->stripe_price_id = $stripePriceId;
        }
        $product->name = $request->input('name');
        $product->tag = '';
        $product->version = $request->input('version');
        $product->slug = $request->input('slug');
        $product->category = $request->input('category');
        $product->isWings = $request->input('isWings');
        $product->price = $request->input('price');
        $product->sxcname = $request->input('sxcname');
        $product->bbb_id = $request->input('bbb_id');
        $product->link = json_decode($request->input('link'), true);
        $product->licensed = $request->input('licensed');
        $product->new = $request->input('isnew');
        $product->autoinstaller = $request->input('autoinstaller');
        $product->recurrent = $request->input('recurrent');
        $product->tab = $request->input('tab');
        $product->tabroute = $request->input('tabroute') ? $request->input('tabroute') : '';
        $product->description = '';
        $product->hide = $request->input('hide');
        $product->extension = $request->input('extension');
        $product->extension_product = $request->input('extension_product') ? $request->input('extension_product') : null;

        $product->save();
        foreach($request->input('descriptions') as $description) {
            $desc = $product->getDescription($description['language']);
            $newdesc = [
                'product_id' => $product->id,
                'description' => $description['description'],
                'language' => $description['language'],
                'tag' => $description['tag']
            ];
            if($desc) {
                $desc->update($newdesc);
            } else {
                ProductsDescription::create($newdesc);
            }
        }
        if ($request->hasFile('zip')) {
            $orders = Orders::where('status', 'completed')->whereJsonContains('products', $product->id)->get();
            foreach($orders as $order) {
                if($order->user->newsletter) {
                    $userEmail = $order->user->email;
                    Mail::to($userEmail)
                        ->send(new ProductUpdateMail($product->id, $product->name, $order->user->name));
                }
            }
        }
        return response()->json(['status' => 'success', 'message' => 'Product updated', 'data' => $product]);
    }

    /**
     * Synchronise les produits avec Stripe.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function syncProducts()
    {
        $user = auth('sanctum')->user();
        if (!$user || $user->role !== 1) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        $products = Products::where(function ($query) {
            $query->where(function ($subquery) {
                $subquery->whereNotNull('stripe_id')
                    ->orWhereNotNull('stripe_price_id');
            })
                ->orWhere('price', '>', 0);
        })->get();
       // $products = Products::all();
        foreach ($products as $product) {
            if (empty($product->stripe_id) || empty($product->stripe_price_id)) {
                // Créer le produit sur Stripe
                $response = Http::asForm()->withHeaders([
                    'Authorization' => 'Bearer ' . config('services.stripe.secret'),
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ])->post('https://api.stripe.com/v1/products', [
                    'name' => $product->name,
                ]);
                if ($response->failed()) {
                    return response()->json(['status' => 'error', 'message' => 'Failed to create product on Stripe'], 500);
                }

                $stripeProduct = $response->json();
                $stripeProductId = $stripeProduct['id'];

                // Créer le prix du produit sur Stripe
                $response = Http::asForm()->withHeaders([
                    'Authorization' => 'Bearer ' . config('services.stripe.secret'),
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ])->post('https://api.stripe.com/v1/prices', [
                    'product' => $stripeProductId,
                    'unit_amount' => $product->price * 100, // Le prix est en centimes
                    'currency' => 'eur',
                ]);

                if ($response->failed()) {
                    return response()->json(['status' => 'error', 'message' => 'Failed to create product price on Stripe'], 500);
                }

                $stripePrice = $response->json();
                $stripePriceId = $stripePrice['id'];

                // Mettre à jour les identifiants Stripe dans la table "products"
                $product->stripe_id = $stripeProductId;
                $product->stripe_price_id = $stripePriceId;
                $product->save();
            }
        }

        return response()->json(['status' => 'success', 'message' => 'Products synchronized with Stripe']);
    }

    public function googleExport()
    {
        $user = auth('sanctum')->user();
        if (!$user || $user->role !== 1) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        $products = Products::all(); // Récupère tous les produits

        $xml = new XMLWriter();
        $xml->openMemory();
        $xml->startDocument('1.0', 'UTF-8');
        $xml->startElement('rss');
        $xml->writeAttribute('version', '2.0');
        $xml->writeAttribute('xmlns:g', 'http://base.google.com/ns/1.0');
        $xml->startElement('channel');
        $xml->writeElement('title', 'Product Feed');
        $xml->writeElement('link', 'https://bagou450.com');
        $xml->writeElement('description', 'List of Bagou450 products');
        $countries = ["AU", "AT", "BE", "CA", "CZ", "FR", "DE", "IE", "IL", "IT", "JP", "NL", "PL", "RO", "KR", "ES", "CH", "GB", "US"];

        foreach ($products as $product) {
            if($product->price > 0 && !strpos($product->name, 'License extension')) {
                $xml->startElement('item');
                $xml->writeElement('g:id', $product->id);
                $xml->writeElement('g:title', $product->name);
                $xml->writeElement('g:description', $product->tag);
                $xml->writeElement('g:link', "https://bagou450.com/product/" . $product->slug);
                $xml->writeElement('g:image_link', "https://api.bagou450.com/storage/logos/" . $product->id . '.webp');
                $xml->writeElement('g:availability', 'in_stock');
                $xml->writeElement('g:price', $product->price . ' EUR');
                $xml->writeElement('g:brand', "Bagou450");
                $xml->writeElement('g:mpn', $product->id);
                $xml->writeElement('g:product_type', "Pterodactyl modules");
                $xml->writeElement('g:is_bundle', $product->category == 'Bundles' ? 'yes' : 'no');
                $xml->writeElement('g:google_product_category', "Software &gt; Computer Software &gt; Business & Productivity Software");
                foreach($countries as $country) {
                    $xml->startElement('g:shipping');
                    $xml->writeElement('g:country', $country);
                    $xml->writeElement('g:price', '0 EUR');
                    $xml->endElement();
                }
                $xml->endElement();
            }

        }

        $xml->endElement();
        $xml->endElement();
        $xml->endDocument();

        $content = $xml->outputMemory();

        return response($content)
            ->header('Content-Type', 'application/xml')
            ->header('Content-Disposition', 'attachment; filename="products.xml"');
    }

}
