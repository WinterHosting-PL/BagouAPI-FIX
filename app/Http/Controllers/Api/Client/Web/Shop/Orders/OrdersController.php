<?php

namespace App\Http\Controllers\Api\Client\Web\Shop\Orders;

use App\Mail\InvoiceMail;
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
use Illuminate\Support\Facades\Auth;
use App\Models\CouponCode;
use League\ISO3166\ISO3166;
use Barryvdh\DomPDF\Facade\Pdf;

class OrdersController extends BaseController
{
    public function get(Request $request): \Illuminate\Http\JsonResponse
    {
        $user = auth('sanctum')->user();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'You need to be authentificated'], 500);
        }
        $order = array();
        $orders = Orders::select('status', 'price', 'products', 'stripe_id', 'id')->where('user_id', '=', $user->id)->get();
        if(!$orders) {
            return response()->json(['status' => 'success', 'data' => ['user' => $user->name, 'orders' => null]], 200);
        }
        foreach ($orders as $ord) {
            $addons = array();
            foreach($ord->products as $addon) {
               $addons[] = Products::where('id', '=', $addon)->select('name')->firstOrFail();

            }
            $order[] = ['products' => $addons, 'price' => $ord->price, 'status' => $ord->status, 'order_id' => $ord->id, 'stripe_id' => $ord->stripe_id];
        }
        return response()->json(['status' => 'success', 'data' => ['user' => $user->name, 'orders' => $order]], 200);

    }



    public function create(Request $request): \Illuminate\Http\JsonResponse
    {

        /*
         * Create a order with mollie as payment gateway
         * Parameters : product (productid), redirectionUrl, webhookUrl (optional)
         */
        $user = auth('sanctum')->user();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'You need to be authentificated'], 500);
        }
        if (!$user->address || !$user->country || !$user->city || !$user->region || !$user->postal_code) {
            return response()->json(['status' => 'error', 'message' => 'You need to link a adress to your account.'], 500);
        }
        $order = Orders::where('user_id', $user->id)->where('products', $request->products)->where('created_at', '<', Carbon::now()->subHours(24)->toDateTimeString())->first();
        if($order) {
            return response()->json(['status' => 'success', 'data' => $order->checkout], 200);
        }

        $product = Products::where('id', '=', $request->product)->firstOrFail();
        $data = [
            'success_url' => "https://privatewebsite.bagou450.com/product/purchase/$product->id",
            'cancel_url' => "https://privatewebsite.bagou450.com/product/purchase/$product->id",
            'currency' => 'EUR',
            'customer_email' => $user->email,
            'customer_creation' => 'always'
        ];
        $data['mode'] = 'payment';
        $items = array();
        foreach ($request->products as $product) {
            $productdata = Products::where('id', '=', $product)->firstOrFail();
            if($productdata->reccurent) {
                $data['mode'] = 'subscription';
                $data['line_items'] = [[
                    'price' => $productdata->stripe_price_id,
                    'quantity' => 1
                ]];
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

        $request = Http::asForm()->withHeaders([
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
            'products' => $request->products,
            'stripe_id' => $request->id,
            'status' => 'incomplete',
            'price' => $request->amount_total,
            'checkout' => $request->url,
            'address' => $user->address,
            'country' => $user->country,
            'city' => $user->city,
            'region' => $user->region,
            'postal_code' => $user->postal_code,
            'name' => $name
        );
        Orders::create($order);

        return response()->json(['status' => 'success', 'data' => $request->url], 200);

    }
    public function status(Request $request): \Illuminate\Http\JsonResponse
    {
        /*
         * Get status of a mollie payment
         * Parameters : order id
         */
        $user = auth('sanctum')->user();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'You need to be authentificated'], 500);
        }
        (new \App\Http\Controllers\Api\Client\Web\Shop\Orders\OrdersController)->updatestatus();

        $order = Orders::where('id', '=', $request->id)->where('user_id', '=', $user->id)->first();

        if ($order) {
            $licenses = array();
            if ($order->status === 'complete') {
                foreach ($order->products as $product) {
                    $productdata = Products::where('id', $product->id)->firstOrFail();
                    if($productdata->licensed) {
                        $licenses[] = array('product' => $product->id, 'product_name' => $product->name, 'license' => License::where('order_id', '=', $order->id)->where('product_id', '=', $product->id)->where('user_id', '=', $user->id)->firstOrFail()->transaction);
                    }
                }
            }
            $order->licenses = $licenses;
            return response()->json(['status' => 'success', 'data' => ['exist' => true, 'order' => $order]], 200);

        }
        return response()->json(['status' => 'success', 'data' => ['exist' => false]], 200);

    }

    public function updatestatus(): \Illuminate\Http\JsonResponse
    {
        /*
         * Update status of all mollie payment
         */
        $request = Http::asForm()->withHeaders([
            'Authorization' => 'Bearer ' . config('services.stripe.secret'),
            'Content-Type' => 'application/x-www-form-urlencoded',
        ]);
        $orders = Orders::where('status', '=', 'incomplete')->get();

        foreach ($orders as $order) {
            $payment = $request->post("https://api.stripe.com/v1/checkout/sessions/$order->stripe_id")->object();
            if($order->created_at->created_at->diffInHours(now()) >= 24 && $payment->payment_status != 'paid') {
                Orders::where('id', '=', $order->id)->update(['status' => 'expired']);
                continue;
            }
            if ($payment->payment_status == 'paid') {
                Orders::where('id', '=', $order->id)->update(['status' => 'complete']);
                $licenses = array();
                $items = array();
                foreach ($order->products as $product) {
                    $addon = Products::where('id', $product->id)->firstOrFail();
                    $user = User::where('id', '=', $order->user_id)->firstOrFail();
                    if($product->licensed) {
                        $transaction = 'aa';
                        while ($transaction === 'aa' or License::where("transaction", '=', $transaction)->exists()) {
                            $bytes = random_bytes(32);
                            $transaction = "bgxw_" . bin2hex($bytes);
                        }
                        $license = ['blacklisted' => false, "sxcid" => null, 'buyer' => $user->name, 'fullname' => $addon->name, 'ip' => [], 'maxusage' => 2, 'product_id' => $addon->id, 'transaction' => $transaction, 'usage' => 0, "buyerid" => 500, 'bbb_id' => $addon->bbb_id, 'bbb_license' => $transaction, 'order_id' => $order->id, 'user_id' => $order->user_id];
                        License::create($license);
                        $licenses[] = $license;
                    }
                    $items[] = [
                        'description' => $addon->name,
                        'quantity' => 1,
                        'price' => $addon->price,
                        'tax' => 0.35,
                    ];
                }



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
                // Création du PDF de la facture
                $pdf = PDF::loadView('invoices.invoice', compact('invoice_number', 'invoice_date', 'due_date', 'customer', 'items'));
                $pdf->setPaper('A4', 'portrait');
                  Mail::to($user->email)
                      ->send(new OrderConfirmed($pdf->download('invoice.pdf')));
                    Mail::to('receipts@bagou450.com')
                        ->send(new OrderConfirmed($pdf->download('invoice.pdf')));
                if($licenses) {
                    Mail::to($user->email)
                        ->send(new TestMail($licenses));
                    Mail::to('receipts@bagou450.com')
                        ->send(new TestMail($licenses));
                }
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
        $archive = new \ZipArchive();
        $archiveName = "$order->id.zip";
        if ($archive->open($archiveName, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === true) {
            foreach ($order->products as $product) {
                $zipFileName = '../addonfiles/' . $product . '.zip';
                if(!file_exists($zipFileName)) {
                    return response()->json(['status' => 'error', 'message' => "Errors can\'t add all files. $zipFileName not exist!"], 500);

                }
                if ($archive->locateName((string) $product) === false) {
                    $archive->addEmptyDir((string) $product); // Crée le dossier avec l'ID
                    $subArchive = new \ZipArchive();
                    if ($subArchive->open($zipFileName) === true) {
                        $subArchive->extractTo($product); // Extrait le contenu du fichier ZIP dans le dossier correspondant
                        $subArchive->close();
                        $subFiles = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($product));
                        foreach ($subFiles as $subFile) {
                            if (!$subFile->isDir()) {
                                $filePath = $subFile->getPathname();
                                $relativePath = $product . '/' . str_replace($product . '/', '', $filePath);
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
        foreach ($order->products as $product) {
            if (is_dir($product)) {
                $this->deleteDirectory($product);
            }
        }
        return response()->download($archiveName, $archiveName, ['Content-Type: application/zip'])->deleteFileAfterSend(true);;

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
                'tax' => 0.35,
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