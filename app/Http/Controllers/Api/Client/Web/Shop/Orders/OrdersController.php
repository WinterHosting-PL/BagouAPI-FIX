<?php

namespace App\Http\Controllers\Api\Client\Web\Shop\Orders;

use App\Mail\InvoiceMail;
use App\Mail\TestMail;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;
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
        $orders = Orders::select('status', 'price', 'product_id', 'id')->where('user_id', '=', $user->id)->get();
        foreach ($orders as $ord) {
            $addon = Products::where('id', '=', $ord->product_id)->select('name', 'version')->firstOrFail();
            array_push($order, ['product' => $addon->name, 'product_id' => $ord->product_id, 'version' => number_format($addon->version, 2), 'price' => $ord->price, 'status' => $ord->status, 'order_id' => $ord->id]);
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
        $coupon = 0;
        $product = Products::where('id', '=', $request->product)->firstOrFail();
        if ($request->couponcode) {
            $co = CouponCode::where('name', '=', $request->couponcode)->first();
            if (!$co) {
                return response()->json(['status' => 'error', 'message' => 'Imposible to found this promotional code.'], 500);
            }
            $reduction = $product->price * $co->value / 100;
            $nouveauPrix = $product->price - $reduction;
            $coupon = strval(number_format($nouveauPrix, 2));
        }
        $mollie = new \Mollie\Api\MollieApiClient();
        $mollie->setApiKey("test_zRSv7R6psR5P4PwKsggnnyE6pJy68G");
        $user = Auth::user();
        $orderfirst = Orders::latest()->first();
        $id = 0;
        if ($orderfirst) {
            $id = $orderfirst->id + 1;
        }
        if (!$user->address || !$user->postal_code || !$user->city || !$user->country || !$user->name || !$user->lastname || !$user->email) {
            return response()->json(['status' => 'error', 'message' => 'Some adress informations are missing.'], 500);
        }
        $data = $mollie->orders->create([
            "amount" => [
                "value" => strval(number_format($product->price + 0.35, 2)),
                "currency" => "EUR",
            ],
            "billingAddress" => [
                "streetAndNumber" => $user->address,
                "postalCode" => $user->postal_code,
                "city" => $user->city,
                "country" => (new ISO3166)->name($user->country)['alpha2'],
                "givenName" => $user->name,
                "familyName" => $user->lastname,
                "email" => $user->email,
            ],
            "shippingAddress" => [
                "streetAndNumber" => $user->address,
                "postalCode" => $user->postal_code,
                "city" => $user->city,
                "country" => (new ISO3166)->name($user->country)['alpha2'],
                "givenName" => $user->name,
                "familyName" => $user->lastname,
                "email" => $user->email,
            ],
            "locale" => "en_US",
            "orderNumber" => "#" . strval($id),
            "redirectUrl" => "https://privatewebsite.bagou450.com/product/purchase/$product->id",
            "webhookUrl" => $request->webhookUrl,
            "metadata" => [
                "order_id" => $id
            ],
            "lines" => [
                [
                    "name" => $product->name,
                    "productUrl" => "https://bagou450.com/product/$product->id",
                    "imageUrl" => "https://cdn.bagou450.com/assets/img/addons/$product->id",
                    "quantity" => 1,
                    "vatRate" => strval(number_format(0, 2)),
                    "vatAmount" => [
                        "currency" => "EUR",
                        "value" => strval(number_format(0, 2)),
                    ],
                    "unitPrice" => [
                        "currency" => "EUR",
                        "value" => strval(number_format($product->price + 0.35, 2)),
                    ],
                    "totalAmount" => [
                        "currency" => "EUR",
                        "value" => strval(number_format($product->price + 0.35, 2)),
                    ]

                ],

            ],
        ]);
        $order = array(
            'id' => $id,
            'user_id' => $user->id,
            'product_id' => $request->product,
            'mollie_id' => $data->id,
            'status' => 'incomplete',
            'price' => $product->price,
            'checkout' => $data->_links->checkout->href
        );
        Orders::create($order);

        return response()->json(['status' => 'success', 'data' => $data->_links->checkout->href], 200);

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

        $order = Orders::where('product_id', '=', $request->id)->where('user_id', '=', $user->id)->where('status', '!=', 'expired')->first();

        if ($order) {
            $addon = Products::where('id', '=', $order->product_id)->firstOrFail();
            if ($order->status === 'complete' && $addon->licensed) {
                $order->license = License::where('order_id', '=', $order->id)->where('user_id', '=', $user->id)->firstOrFail()->transaction;
            }
            return response()->json(['status' => 'success', 'data' => ['exist' => true, 'order' => $order]], 200);

        }
        return response()->json(['status' => 'success', 'data' => ['exist' => false]], 200);

    }

    public function updatestatus(): \Illuminate\Http\JsonResponse
    {
        /*
         * Update status of all mollie payment
         */
        $mollie = new \Mollie\Api\MollieApiClient();
        $mollie->setApiKey("test_zRSv7R6psR5P4PwKsggnnyE6pJy68G");
        $orders = Orders::where('status', '=', 'incomplete')->get();
        foreach ($orders as $order) {
            $payment = $mollie->orders->get($order->mollie_id);
            if ($payment->isPaid()) {
                Orders::where('id', '=', $order->id)->update(['status' => 'complete']);
                $addon = Products::where('id', '=', $order->product_id)->firstOrFail();
                $user = User::where('id', '=', $order->user_id)->firstOrFail();

                $transaction = '5LB094126U433992N';
                while ($transaction === '5LB094126U433992N' or License::where("transaction", '=', $transaction)->exists()) {
                    $bytes = random_bytes(32);
                    $transaction = "bgx4_" . bin2hex($bytes);
                }

                $license = ['blacklisted' => false, "sxcid" => null, 'buyer' => $user->name, 'fullname' => $addon->name, 'ip' => [], 'maxusage' => 2, 'name' => $addon->id, 'transaction' => $transaction, 'usage' => 0, "buyerid" => 500, 'bbb_id' => $addon->bbb_id, 'bbb_license' => $transaction, 'order_id' => $order->id, 'user_id' => $order->user_id];
                License::create($license);
                $licenses = array();
                array_push($licenses, $license);

                // Récupération des données pour la facture
                $invoice_number = "#$order->id"; // Numéro de la facture
                $invoice_date = $order->created_at->format('Y-m-d'); // Date de la facture
                $due_date = $order->created_at->format('Y-m-d'); // Date limite de paiement
                $name = "$user->firstname $user->lastname";
                if ($user->society && $user->society !== '') {
                    $name = $user->society;
                }
                $customer = [
                    'name' => $name,
                    'address' => $user->address,
                    'city' => $user->city,
                    'country' => $user->country,
                    'region' => $user->region,
                    'postal_code' => $user->postal_code,
                    'email' => $user->email,
                ];
                $items = [
                    [
                        'description' => $addon->name,
                        'quantity' => 1,
                        'price' => $addon->price,
                        'tax' => 0.35,
                    ],
                ];
                // Création du PDF de la facture
                $pdf = PDF::loadView('invoices.invoice', compact('invoice_number', 'invoice_date', 'due_date', 'customer', 'items'));
                $pdf->setPaper('A4', 'portrait');

                Mail::to($user->email)->send(new InvoiceMail($order->id, $customer, $items, $invoice_date, $due_date, $pdf));


                Mail::to($user->email)
                    ->send(new TestMail('Bagou450', 'Cloud Servers', $licenses));
                Mail::to('receipts@bagou450.com')
                    ->send(new TestMail('Bagou450', 'Cloud Servers', $licenses));

            }
            if ($payment->isExpired()) {
                Orders::where('id', '=', $order->id)->update(['status' => 'expired']);
            }

        }
        return response()->json(['status' => 'success'], 200);




    }
    public function generateInvoice(Request $request): \Illuminate\Http\JsonResponse
    {
        $user = auth('sanctum')->user();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'You need to be authentificated'], 500);
        }
        $order = Orders::where('id', '=', $request->order)->where('user_id', '=', $user->id)->firstOrFail();
        $mollie = new \Mollie\Api\MollieApiClient();
        $mollie->setApiKey("test_zRSv7R6psR5P4PwKsggnnyE6pJy68G");
        $invoice = $mollie->invoices->get($request->order);
        return response()->json(['status' => 'success', 'data' => $invoice->_links->pdf->href], 200);

    }

    public function orderDetails(Request $request): \Illuminate\Http\JsonResponse
    {
        $user = auth('sanctum')->user();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'You need to be authentificated'], 500);
        }
        $order = Orders::where('id', '=', $request->order)->where('user_id', '=', $user->id)->firstOrFail();
        $mollie = new \Mollie\Api\MollieApiClient();
        $mollie->setApiKey("test_zRSv7R6psR5P4PwKsggnnyE6pJy68G");
        $order = $mollie->orders->get($request->order);
        return response()->json(['status' => 'success', 'data' => $order], 200);

    }

    public function getDownloadlink(Request $request, $order): \Illuminate\Http\JsonResponse
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
        return response()->json(['status' => 'success', 'data' => "/orders/downloads/$rand_str"], 200);

    }
    public function download(Request $request, $token)
    {
        $user = auth('sanctum')->user();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'You need to be authentificated'], 500);
        }
        $order = Orders::where('user_id', '=', $user->id)->where('token', '=', $token)->firstOrFail();
        $addon = Products::where('id', '=', $order->product_id)->firstOrFail();
        $order->update(['token' => '']);
        $order->save();
        return response()->download("../addonfiles/$addon->id.zip", "$addon->name.zip", ['Content-Type: application/zip']);

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
        $addon = Products::where('id', '=', $order->product_id)->firstOrFail();

        // Récupération des données pour la facture
        $invoice_number = "#$order->id"; // Numéro de la facture
        $invoice_date = $order->created_at->format('Y-m-d'); // Date de la facture
        $due_date = $order->created_at->format('Y-m-d'); // Date limite de paiement
        $name = "$user->firstname $user->lastname";
        if ($user->society && $user->society !== '') {
            $name = $user->society;
        }
        $customer = [
            'name' => $name,
            'address' => $user->address,
            'city' => $user->city,
            'country' => $user->country,
            'region' => $user->region,
            'postal_code' => $user->postal_code,
            'email' => $user->email,
        ];
        $items = [
            [
                'description' => $addon->name,
                'quantity' => 1,
                'price' => $addon->price,
                'tax' => 0.35,
            ],
        ];
        // Création du PDF de la facture
        $pdf = PDF::loadView('invoices.invoice', compact('invoice_number', 'invoice_date', 'due_date', 'customer', 'items'));
        $pdf->setPaper('A4', 'portrait');



        return $pdf->download('invoice.pdf');
    }


}