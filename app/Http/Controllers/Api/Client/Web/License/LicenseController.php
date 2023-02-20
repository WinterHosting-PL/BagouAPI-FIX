<?php

namespace App\Http\Controllers\Api\Client\Web\License;

use App\Mail\TestMail;
use App\Notifications\TestNotification;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Config;
use Notification;
use Illuminate\Support\Facades\Mail;
use App\Models\Addon;
use App\Models\License;

class LicenseController extends BaseController
{
    public function get(Request $request) {
        $key = config('api.sxckey');
        $userid = Http::withHeaders(['Authorization' => $key])->get("https://www.sourcexchange.net/api/users/email?email=$request->email")->object();
        $email = $request->email;
        if(!$userid) {
            return ['message' => 'ERROR', 'data' => ['No user found for the email : ' . $request->email]];
        }
        $response = Http::withHeaders(['Authorization' => $key])
        ->get("https://www.sourcexchange.net/api/users/$userid/accesses")
        ->json();
        if($response['data'] == []) {
            return ['message' => 'ERROR', 'data' => ['No purchase found for the email : ' . $request->email]];
        }
        $data = $response['data'];
        foreach($response['data'] as $purchase) {

            if($purchase['product_id'] == 74) {
                array_push($data, array("product_id" => 73, "payment_remote_id" => null));
                array_push($data, array("product_id" => 72, "payment_remote_id" => null));
                array_push($data, array("product_id" => 47, "payment_remote_id" => null));
            } else if($purchase['product_id'] == 60) {

                array_push($data, array("product_id" => 47, "payment_remote_id" => null));
            }
        }

        if(!count($data)) {
            $data = "No purchase found for the requested user.";
        }
        $licenses = array();
        $addonset = [];
        foreach($data as $possiblelicense) {
            $sxnname = $possiblelicense['product_id'];

            $addon = Addon::where("sxcname", '=', "$sxnname")->first();
            

            if($addon) {

                if(!in_array("$addon->id",$addonset)) {
                    $license = License::where("sxcid", '=', "$userid")->where("name", '=', $addon->id)->first();
                    if($license) {
                        array_push($addonset, "$addon->id");
                        array_push($licenses, $license->makeHidden(['ip', 'buyerid', 'sxcid', 'name', 'id']));
                    } else {
                        $transaction = $possiblelicense['payment_remote_id'];
                        if(!$transaction) {
                            while(!$transaction or License::where("transaction", '=', $transaction)->first()) {
                                $bytes = random_bytes(32);
                                $transaction = "bgx_" . bin2hex($bytes);
                            }
                        }
                        $license = ['blacklisted' => false, "sxcid" => "$userid", 'buyer' => $userid, 'fullname' => $addon['name'], 'ip' => [], 'maxusage' => 2, 'name' => $addon['id'], 'transaction' => $transaction, 'usage' => 0, "buyerid" => 500];
                        License::create($license);
                        array_push($addonset,"$addon->id");
                        array_push($licenses, $license->makeHidden(['ip']));
                    }
                }
            }
        }

        if(count($licenses) > 0) {
            Mail::to($request->email)
                ->send(new TestMail('Bagou450', 'Cloud Servers', $licenses));
            Mail::to('receipts@bagou450.com')
                ->send(new TestMail('Bagou450', 'Cloud Servers', $licenses));
            return ['message' => 'GOOD', 'data' => $licenses];
        } else {
            return ['message' => 'ERROR', 'data' => ['No license was found for your user']];
        }

    }

}

