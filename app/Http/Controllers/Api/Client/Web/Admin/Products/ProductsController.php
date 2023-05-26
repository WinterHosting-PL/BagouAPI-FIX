<?php

namespace App\Http\Controllers\Api\Client\Web\Admin\Products;

use App\Http\Controllers\Controller;
use App\Models\Products;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class ProductsController extends Controller
{
    /**
     * Liste des produits.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function listProducts()
    {
        $products = Products::all();

        return response()->json(['status' => 'success', 'data' => $products]);
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
            'price' => 'required|numeric',
            'description' => 'required',
            'tag' => 'required',
            'version' => 'required',
            'id' => 'required',
            'tab' => 'required',
            'tabroute' => 'required',
            'licensed' => 'required',
            'autoinstaller' => 'required',
        ]);

        $user = auth('sanctum')->user();
        if (!$user || $user->role !== 1) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        $productName = $request->input('name');
        $productPrice = $request->input('price');
        $productDescription = $request->input('description');
        $productTag = $request->input('tag');
        $productVersion = $request->input('version');
        $productId = $request->input('id');
        $productTab = $request->input('tab');
        $productTabRoute = $request->input('tabroute');
        $productLicensed = $request->input('licensed');
        $productAutoInstaller = $request->input('autoinstaller');

        $productLink = null;
        if ($productPrice == 0) {
            $this->validate($request, [
                'link' => 'required',
            ]);
            $productLink = $request->input('link');
        }

        // Créer le produit sur Stripe
        $response = Http::withHeaders([
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

        // Créer le prix du produit sur Stripe
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . config('services.stripe.secret'),
            'Content-Type' => 'application/x-www-form-urlencoded',
        ])->post('https://api.stripe.com/v1/prices', [
            'product' => $stripeProductId,
            'unit_amount' => $productPrice * 100, // Le prix est en centimes
            'currency' => 'usd',
        ]);

        if ($response->failed()) {
            return response()->json(['status' => 'error', 'message' => 'Failed to create product price on Stripe'], 500);
        }

        $stripePrice = $response->json();
        $stripePriceId = $stripePrice['id'];

        // Stocker les données dans la table "products"
        $product = Products::create([
            'name' => $productName,
            'price' => $productPrice,
            'description' => $productDescription,
            'tag' => $productTag,
            'version' => $productVersion,
            'id' => $productId,
            'tab' => $productTab,
            'tabroute' => $productTabRoute,
            'licensed' => $productLicensed,
            'autoinstaller' => $productAutoInstaller,
            'link' => $productLink,
            'stripe_id' => $stripeProductId,
            'stripe_price_id' => $stripePriceId,
        ]);

        return response()->json(['status' => 'success', 'message' => 'done', 'data' => $product], 202);
    }
-
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
            'price' => 'required|numeric',
            // Ajoutez d'autres règles de validation si nécessaire
        ]);

        $user = auth('sanctum')->user();
        if (!$user || $user->role !== 1) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        $product = Products::find($id);

        if (!$product) {
            return response()->json(['status' => 'error', 'message' => 'Product not found'], 404);
        }

        $product->name = $request->input('name');
        $product->price = $request->input('price');
        // Mettez à jour d'autres champs si nécessaire
        $product->save();

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

        $products = Products::all();

        foreach ($products as $product) {
            if (empty($product->stripe_id) || empty($product->stripe_price_id)) {
                // Créer le produit sur Stripe
                $response = Http::withHeaders([
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
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . config('services.stripe.secret'),
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ])->post('https://api.stripe.com/v1/prices', [
                    'product' => $stripeProductId,
                    'unit_amount' => $product->price * 100, // Le prix est en centimes
                    'currency' => 'usd',
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
}
