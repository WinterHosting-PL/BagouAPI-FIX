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
        $response = Http::withHeaders(['Authorization' => $key])
        ->get("https://www.sourcexchange.net/api/users/$request->userid")
        ->json();

        $data = $response['data'];
        $name = $data['name'];
        $email = $data['email'];

        $response = Http::withHeaders(['Authorization' => $key])
        ->get("https://www.sourcexchange.net/api/users/$request->userid/accesses")
        ->json();
        $data = $response['data'];
        foreach($response['data'] as $purchase) {
            if($purchase['product_id'] == 74) {
                array_push($data, array("product_id" => 73, "payment_remote_id" => null));
                array_push($data, array("product_id" => 72, "payment_remote_id" => null));
                array_push($data, array("product_id" => 47, "payment_remote_id" => null));
            } else if($purchase['product_id'] == 60) {
                array_push($data, array("product_id" => 57, "payment_remote_id" => null));
            }
        }
        if(!count($data)) {
            $data = "No purchase found for the user $name";
        }
        $licenses = array();
        $addonset = [];
        foreach($data as $possiblelicense) {
            $sxnname = $possiblelicense['product_id'];
            $addon = Addon::where("sxcname", '=', "$sxnname")->first();
            

            if($addon) {
                if(!in_array("$addon->id",$addonset)) {
                    $license = License::where("sxcid", '=', "$request->userid")->where("name", '=', $addon->id)->first();
                    if($license) {
                        array_push($addonset, "$addon->id");
                        array_push($licenses, $license);
                    } else {
                        $transaction = $possiblelicense['payment_remote_id'];
                        if(!$transaction) {
                            while(!$transaction or License::where("transaction", '=', $transaction)->first()) {
                                $bytes = random_bytes(32);
                                $transaction = "bgx_" . bin2hex($bytes);
                            }
                        }
                        $license = ['blacklisted' => false, "sxcid" => "$request->userid", 'buyer' => $name, 'fullname' => $addon['name'], 'ip' => [], 'maxusage' => 2, 'name' => $addon['id'], 'transaction' => $transaction, 'usage' => 0, "buyerid" => 500];
                        License::create($license);
                        array_push($addonset,"$addon->id");
                        array_push($licenses, $license);
                    }
                }
            }
        }
        Mail::to($email)
            ->send(new TestMail('Bagou450', 'Cloud Servers', $licenses));
        return $licenses;
    }

}

