<?php

namespace App\Http\Controllers\Api\Client\Web\Shop\Orders;

use App\Mail\OrderConfirmed;
use App\Mail\TestMail;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use App\Models\Products;
use App\Models\Orders;
use App\Models\User;
use App\Models\License;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Validator;

class OrdersController extends BaseController
{
    public function get(Request $request): \Illuminate\Http\JsonResponse
    {
        $user = auth('sanctum')->user();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'You need to be authenticated'], 500);
        }

        $perPage = 10; // Nombre d'éléments par page
        $currentPage = $request->input('page', 1); // Récupère le numéro de page à partir de la requête

        $ordersQuery = Orders::select('status', 'price', 'products', 'stripe_id', 'id')
            ->where('user_id', '=', $user->id);

        $orders = $ordersQuery->paginate($perPage, ['*'], 'page', $currentPage);

        if ($orders->isEmpty()) {
            return response()->json(['status' => 'success', 'data' => ['user' => $user->name, 'total' => 0, 'orders' => null]], 200);
        }

        $order = [];

        foreach ($orders as $ord) {
            $addons = [];
            foreach ($ord->products as $addon) {
                $addons[] = Products::where('id', '=', $addon)->select('name')->firstOrFail();
            }
            $order[] = ['products' => $addons, 'price' => $ord->price, 'status' => $ord->status, 'order_id' => $ord->id, 'stripe_id' => $ord->stripe_id];
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'user' => $user->name,
                'total' => $orders->total(),
                'orders' => $order,
            ],
        ], 200);

    }



    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'products' => 'required|array',
            'products.*' => 'integer', // This validates each element of the array as an integer
        ]);

        if ($validator->fails()) {
            // Handle validation failure
            // For example, you can return a response with error messages
            return response()->json(['status' => 'error', 'message' => 'Refresh the page and try again.'], 422);
        }
        /*
         * Create a order with mollie as payment gateway
         * Parameters : product (productid), redirectionUrl, webhookUrl (optional)
         */
        $user = auth('sanctum')->user();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'You need to be authentificated'], 500);
        }
        if (!$user->address || !$user->country || !$user->city || !$user->region || !$user->postal_code) {
            return response()->json(['status' => 'error', 'message' => 'You need to link a address to your account.'], 500);
        }
        $allproducts = $request->products;
        if($request->extension) {
            $productdata = Products::where('extension_product', '=', $request->products[0])->where('extension', true)->first();
            if(!$productdata) {
                return response()->json(['status' => 'error', 'message' => 'No extension_product found'], 500);
            }
            $allproducts = [$productdata->id];
        }
        $order = Orders::where('user_id', $user->id)->where('products', $allproducts)->where('created_at', '<', Carbon::now()->subHours(24)->toDateTimeString())->first();
        if($order) {
            return response()->json(['status' => 'success', 'data' => $order->checkout], 200);
        }

        $order = Orders::latest()->first();
        if($order) {
            $order = Orders::latest()->first()->id+1;
        } else {
            $order = 0;
        }
        $data = [
            'success_url' => "https://privatewebsite.bagou450.com/order/$order",
            'cancel_url' => "https://privatewebsite.bagou450.com/order/$order",
            'currency' => 'EUR',
            'customer_email' => $user->email,
            'customer_creation' => 'always'
        ];
        $data['mode'] = 'payment';
        $items = array();
        $totalPrice = 0;
        foreach ($allproducts as $product) {
            $productdata = Products::where('id', '=', $product)->firstOrFail();

            $totalPrice += $productdata->price;
            if($productdata->reccurent) {
                $data['mode'] = 'subscription';
                $data['customer_creation'] = null;
                break;
            }
            $items[] = array('price' => $productdata->stripe_price_id, 'quantity' => 1);

        }

        $data['line_items'] = $items;
        $orderfirst = Orders::latest()->first();
        $id = 0;
        if ($orderfirst) {
            $id = $orderfirst->id + 1;
        }

        $therequest = Http::asForm()->withHeaders([
            'Authorization' => 'Bearer ' . config('services.stripe.secret'),
            'Content-Type' => 'application/x-www-form-urlencoded',
        ])->post('https://api.stripe.com/v1/checkout/sessions', $data)->object();
        $name = "$user->firstname $user->lastname";
        if ($user->society && $user->society !== '') {
            $name = $user->society;
        }
        $order = array(
            'id' => $id,
            'user_id' => $user->id,
            'products' => $allproducts,
            'stripe_id' => $therequest->id,
            'status' => 'incomplete',
            'price' => $totalPrice,
            'checkout' => $therequest->url,
            'address' => $user->address,
            'country' => $user->country,
            'city' => $user->city,
            'region' => $user->region,
            'postal_code' => $user->postal_code,
            'name' => $name
        );
        Orders::create($order);

        return response()->json(['status' => 'success', 'data' => $therequest->url], 200);

    }
    public function status(Request $request): \Illuminate\Http\JsonResponse
    {
        /*
         * Get status of a mollie payment
         * Parameters : order id
         */
        $user = auth('sanctum')->user();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'You need to be authentificated.'], 500);
        }
        (new \App\Http\Controllers\Api\Client\Web\Shop\Orders\OrdersController)->updatestatus();

        $order = Orders::where('id', '=', $request->id)->where('user_id', '=', $user->id)->first();

        if ($order) {
            $productsinfos = array();
            if ($order->status === 'complete') {
                foreach ($order->products as $product) {
                    $license = License::where('order_id', '=', $order->id)->where('product_id', '=', $product)->where('user_id', '=', $user->id)->first();
                    if($license) {
                        $license = $license->license;
                    } else {
                        $license = null;
                    }
                    $addon = Products::where('id', $product)->firstOrFail();

                    $productsinfos[] = array('id' => $product, 'name' => $addon->name, 'tag' => $addon->tag, 'price' => $addon->price,'license' => $license);
                }
            } else {
                foreach ($order->products as $product) {
                    $addon = Products::where('id', $product)->firstOrFail();
                    $productsinfos[] = array('id' => $product, 'name' => $addon->name, 'tag' => $addon->tag, 'price' => $addon->price, 'icon' => $addon->icon);
                }
            }
            $order->products = $productsinfos;
            return response()->json(['status' => 'success', 'data' => ['exist' => true, 'order' => $order]], 200);

        }
        return response()->json(['status' => 'success', 'data' => ['exist' => false]], 200);

    }

    public function updatestatus()
    {
        /*
         * Update status of all orders
         */
        $request = Http::asForm()->withHeaders([
            'Authorization' => 'Bearer ' . config('services.stripe.secret'),
            'Content-Type' => 'application/x-www-form-urlencoded',
        ]);
        $orders = Orders::where('status', '=', 'incomplete')->get();
        foreach ($orders as $order) {
            $payment = $request->get("https://api.stripe.com/v1/checkout/sessions/$order->stripe_id")->object();
            if($order->created_at->diffInHours(now()) >= 24 && $payment->payment_status != 'paid') {
                Orders::where('id', '=', $order->id)->update(['status' => 'expired']);
                continue;
            }
            if ($payment->payment_status == 'paid') {
                $licenses = array();
                $items = array();
                foreach ($order->products as $product) {
                    $addon = Products::where('id', $product)->firstOrFail();
                    $user = User::where('id', '=', $order->user_id)->firstOrFail();
                    $license = null;
                    if($addon->licensed && !$addon->extension) {
                        $transaction = 'aa';
                        while ($transaction === 'aa' or License::where('license', $transaction)->exists()) {
                            $bytes = random_bytes(32);
                            $transaction = "bgxw_" . bin2hex($bytes);
                        }
                        $license = ['blacklisted' => false, 'buyerid' => 500, 'user_id' => $user->id, 'buyer' => $user->firstname . ' ' . $user->lastname, 'product_id' => $product, 'ip' => [], 'maxusage' => 2, 'license' => $transaction, 'usage' => 0, 'order_id' => $order->id];
                        License::create($license);
                        $license = $transaction;
                    }
                    if($addon->extension) {

                        $extension = $addon->extension_product;

                        $license = License::where('product_id', '=', $addon->extension_product)->where('user_id', $order->user_id)->first();
                        if(!$license) {
                            return response()->json(['status' => 'error', 'message' => 'Extension product not found'], 404);
                        }

                        $license->maxusage +=1;
                        $license->save();
                        $license = $license->license;
                    }
                    $items[] = [
                        'description' => $addon->name,
                        'name' => $addon->name,
                        'quantity' => 1,
                        'price' => $addon->price,
                        'license' => $license
                    ];
                }



                $invoice_number = "#$order->id"; // Numéro de la facture
                $invoice_date = $order->created_at->format('Y-m-d'); // Date de la facture
                $due_date = $order->created_at->format('Y-m-d'); // Date limite de paiement
                $customer = [
                    'name' => $order->name,
                    'address' => $order->address,
                    'city' => $order->city,
                    'country' => $order->country,
                    'region' => $order->region,
                    'postal_code' => $order->postal_code,
                    'email' => $user->email,
                ];
                // Création du PDF de la facture
                $pdf = PDF::loadView('invoices.invoice', compact('invoice_number', 'invoice_date', 'due_date', 'customer', 'items'));
                $pdf->setPaper('A4', 'portrait');

                  Mail::to($user->email)
                      ->send(new OrderConfirmed($pdf, $items, $order->id, $order->created_at, $order->created_at, $customer));
                    Mail::to('receipts@bagou450.com')
                        ->send(new OrderConfirmed($pdf, $items, $order->id, $order->created_at, $order->created_at, $customer));
                Orders::where('id', '=', $order->id)->update(['status' => 'complete']);

            }
        }
        return response()->json(['status' => 'success'], 200);




    }

    public function orderDetails(Request $request): \Illuminate\Http\JsonResponse
    {
        $user = auth('sanctum')->user();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'You need to be authentificated'], 500);
        }
        return response()->json(['status' => 'success', 'data' => Orders::where('id', '=', $request->order)->where('user_id', '=', $user->id)->firstOrFail()], 200);

    }

    public function getDownloadlink(Request $request, $order): \Illuminate\Http\JsonResponse
    {
        $user = auth('sanctum')->user();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'You need to be authentificated'], 500);
        }
        $order = Orders::where('id', '=', $order)->where('user_id', '=', $user->id)->where('status', 'complete')->first();
        if(!$order) {
            return response()->json(['status' => 'error', 'message' => 'No orders found!'], 500);
        }
        $token = bin2hex(random_bytes(128));
        while (Orders::where('token', $token)->exists()) {
            $token = bin2hex(random_bytes(128));
        }

        $order->update(['token' => $token]);

        return response()->json(['status' => 'success', 'data' => "/orders/downloads/$token"], 200);
    }
    public function download(Request $request, $token)
    {
        $user = auth('sanctum')->user();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'You need to be authentificated'], 500);
        }
        $order = Orders::where('user_id', '=', $user->id)->where('token', '=', $token)->where('status', 'complete')->first();
        if(!$order) {
            return response()->json(['status' => 'error', 'message' => 'No orders found!'], 500);
        }
        $randomString = bin2hex(random_bytes(8));
        $basePath = storage_path('app/private/tmp/' . $randomString);

        if (!file_exists($basePath)) {
            mkdir($basePath, 0777, true);
        }
        $archive = new \ZipArchive();
        $archiveName = storage_path('app/private/tmp/') . "/$order->id.zip";
        if ($archive->open($archiveName, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === true) {
            foreach ($order->products as $product) {
                $productName = Products::where('id', $product)->firstOrFail()->name;
                $productDir = $basePath . '/' . $productName;

                if (is_dir($productDir)) {
                    $this->deleteDirectory($productDir);
                }

                $zipFileName = storage_path('app/private/zips/' . $product . '.zip');
                if(!file_exists($zipFileName)) {
                    return response()->json(['status' => 'error', 'message' => "Errors can't add all files. $zipFileName not exist!"], 500);
                }

                if ($archive->locateName((string) $productName) === false) {
                    $archive->addEmptyDir((string) $productName);
                    $subArchive = new \ZipArchive();
                    if ($subArchive->open($zipFileName) === true) {
                        $subArchive->extractTo($productDir);
                        $subArchive->close();
                        $subFiles = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($productDir));
                        foreach ($subFiles as $subFile) {
                            if (!$subFile->isDir()) {
                                $filePath = $subFile->getPathname();
                                $relativePath = $productName . '/' . str_replace($productDir . '/', '', $filePath);
                                $archive->addFile($filePath, $relativePath);
                            }
                        }

                    } else {
                        return response()->json(['status' => 'error', 'message' => 'Can\'t open a zip file..'], 500);

                    }
                }
                $archive->deleteName($product);
            }
            $archive->close();
        } else {
            return response()->json(['status' => 'error', 'message' => 'Can\'t create the file.'], 500);
        }

        $order->update(['token' => '']);
        $order->save();
        $this->deleteDirectory($basePath);

        return response()->download($archiveName, "$order->id.zip", ['Content-Type: application/zip'])->deleteFileAfterSend();

    }
    public function getDownloadOnelink(String $id): \Illuminate\Http\JsonResponse
    {
        $user = auth('sanctum')->user();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'You need to be authentificated'], 500);
        }
        $order = Orders::where('user_id', '=', $user->id)->where('status', 'complete')
            ->where(function ($query) use ($id) {
                $query->whereJsonContains('products', (int)$id);
            })->first();
        if(!$order) {
            return response()->json(['status' => 'error', 'message' => 'No orders found!'], 500);
        }
        $token = bin2hex(random_bytes(128));
        while (Orders::where('token', $token)->exists()) {
            $token = bin2hex(random_bytes(128));
        }

        $order->update(['token' => $token]);

        return response()->json(['status' => 'success', 'data' => "/orders/downloadOne/$token"], 200);
    }
    public function downloadOne(Request $request, $token)
    {
        $user = auth('sanctum')->user();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'You need to be authentificated'], 500);
        }
        $order = Orders::where('user_id', '=', $user->id)->where('token', '=', $token)->where('status', 'complete')
            ->where(function ($query) use ($request) {
                $query->whereJsonContains('products', (int)$request->product);
            })->first();
        if(!$order) {
            return response()->json(['status' => 'error', 'message' => 'No orders found!'], 404);
        }
        if ($order && in_array($request->product, $order->products)) {
            // Retrieve the product's name from the database
            $product = Products::where('id', $request->product)->firstOrFail();

            // Build the file path
            $filePath = storage_path('app/private/zips/' . $product->id . '.zip');

            return response()->download($filePath, "$product->name.zip");
        }
        return response()->json(['status' => 'error', 'message' => 'No orders found!'], 404);

    }
    public function downloadInvoiceLink(Request $request, $order)
    {
        $user = auth('sanctum')->user();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'You need to be authentificated'], 500);
        }
        $order = Orders::where('id', '=', $order)->where('user_id', '=', $user->id)->firstOrFail();
        $rand_str = bin2hex(random_bytes(128));
        while (Orders::where('token', '=', $rand_str)->exists()) {
            $rand_str = bin2hex(random_bytes(128));
        }
        $order->update(['token' => $rand_str]);
        $order->save();
        return response()->json(['status' => 'success', 'data' => "/orders/downloadInvoice/$rand_str"], 200);
    }
    public function downloadInvoice(Request $request, $token)
    {
        $user = auth('sanctum')->user();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'You need to be authentificated'], 500);
        }
        $order = Orders::where('user_id', '=', $user->id)->where('token', '=', $token)->firstOrFail();

        // Récupération des données pour la facture
        $invoice_number = "#$order->id"; // Numéro de la facture
        $invoice_date = $order->created_at->format('Y-m-d'); // Date de la facture
        $due_date = $order->created_at->format('Y-m-d'); // Date limite de paiement
        $customer = [
            'name' => $order->name,
            'address' => $order->address,
            'city' => $order->city,
            'country' => $order->country,
            'region' => $order->region,
            'postal_code' => $order->postal_code,
            'email' => $user->email,
        ];
        $items = [];
        foreach($order->products as $product) {
            $addon = Products::where('id', '=', $product)->firstOrFail();
            $items[] = [
                'description' => $addon->name,
                'quantity' => 1,
                'price' => $addon->price,
            ];
        }
        $status = $order->status;
        // Création du PDF de la facture

        $pdf = PDF::loadView('invoices.invoice', compact('invoice_number', 'invoice_date', 'due_date', 'customer', 'items', 'status'));
        $pdf->setPaper('A4', 'portrait');



        return $pdf->download('invoice.pdf');
    }

    function deleteDirectory($directory) {
        if (!is_dir($directory)) {
            return;
        }

        $files = glob($directory . '/*');

        foreach ($files as $file) {
            if (is_dir($file)) {
                $this->deleteDirectory($file);
            } else {
                unlink($file);
            }
        }

        rmdir($directory);
    }
}