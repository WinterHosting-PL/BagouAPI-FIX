<?php

namespace App\Http\Controllers\Api\Client\Web\License;

use App\Mail\TestMail;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Config;
use Notification;
use Illuminate\Support\Facades\Mail;
use App\Models\Products;
use App\Models\License;
use App\Http\Controllers\Api\Client\Web\License\LicenseResource;

class LicenseController extends BaseController
{
    public function get(Request $request): \Illuminate\Http\JsonResponse
    {

        $user = auth('sanctum')->user();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'You need to be logged!.'], 500);
        }

        $licensesQuery = License::query();
        if ($user->role !== 1) {
            $licensesQuery->where('user_id', '=', $user->id);
        }
        $search = $request->search;
        if ($search) {
            $licensesQuery->where(function ($query) use ($search) {
                $query->where('ip', 'LIKE', '%' . $search . '%')
                    ->orWhere('license', 'LIKE', '%' . $search . '%')
                    ->orWhere('user_id', 'LIKE', '%' . $search . '%');
            });
        }
        $page = $request->page ?? 1;
        $total = $licensesQuery->count();
        $licenses = $licensesQuery->paginate(10, ['*'], 'page', $page);
        $doneLicenses = [];
        foreach ($licenses as $license) {
            $ips = [];
            foreach ($license->ip as $ip) {
                $ips[] = Crypt::decrypt($ip);
            }
            $doneLicenses[] = [
                'product' => $license->product->name,
                'product_id' => $license->product->id,
                'ip' => $ips,
                'maxusage' => $license->maxusage,
                'license' => $license->license,
                'usage' => $license->usage,
                'version' => $license->version,
                'order_id' => $license->order_id,
            ];
        }
        return response()->json(
            ['status' => 'success', 'data' =>
                ['user' => $user->name,
                    'total' => $total,
                    'license' => $doneLicenses]], 200);

    }

    public function deleteIp(Request $request, $license): \Illuminate\Http\JsonResponse
    {
        /*
        * Cette fonction a pour but de supprimer une utilisation de la license.
        * Pour ce faire il faut supprimer l'ip entrer en parametre de la license. Plus précisement de la colone 'ip' de la license.
        * Ensuite il faut enlever une utilisation a cette derniere.
        */
        $user = auth('sanctum')->user();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'You need to be logged!.'], 500);
        }
        $license = License::where('license', '=', $license)->where('user_id', '=', $user->id)->firstOrFail();
        if ($license->usage < 1) {
            return response()->json(['status' => 'error', 'message' => 'This license is not used.'], 500);
        }
        if ($license->user_id == $user->id) {
            $iplist = array();
            foreach ($license->ip as $licen) {
                if (Crypt::decrypt($licen) !== $request->ip) {
                    array_push($iplist, $licen);
                }
            }
            $license->ip = $iplist;
            $license->usage--;
            $license->save();
            return response()->json(['status' => 'success', 'message' => 'The license was updated successfully!'], 200);
        }
        return response()->json(['status' => 'error', 'message' => 'License is not owned by the logged user.'], 500);
    }

    public function sendLicense(Request $request): \Illuminate\Http\JsonResponse
    {
        $user = auth('sanctum')->user();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'You need to be logged!.'], 500);
        }
        $key = config('api.sxckey');
        if ($request->type === 'ssx') {
            $userid = Http::withHeaders(['Authorization' => $key])->get("https://www.sourcexchange.net/api/users/email?email=$request->userid")->object();
            $email = $request->userid;
            $response = Http::withHeaders(['Authorization' => $key])
                ->get("https://www.sourcexchange.net/api/users/$userid/accesses")
                ->json();
            $data = $response['data'];
            foreach ($response['data'] as $purchase) {

                if ($purchase['product_id'] == 74) {
                    array_push($data, array("product_id" => 73, "payment_remote_id" => null));
                    array_push($data, array("product_id" => 72, "payment_remote_id" => null));
                    array_push($data, array("product_id" => 47, "payment_remote_id" => null));
                } else if ($purchase['product_id'] == 60) {

                    array_push($data, array("product_id" => 47, "payment_remote_id" => null));
                }
            }

            if (!count($data)) {
                $data = "No purchase found for the requested user.";
            }
            $licenses = array();
            $addonset = [];
            foreach ($data as $possiblelicense) {
                $sxnname = $possiblelicense['product_id'];
                $addon = Products::where("sxcname", '=', "$sxnname")->first();
                if ($addon) {

                    if (!in_array("$addon->id", $addonset)) {
                        $license = License::where("sxcid", '=', "$userid")->where("name", '=', $addon->id)->first();
                        if ($license) {
                            if ($user) {
                                License::where('sxcid', '=', $userid)->where("name", '=', $addon->id)->update(['user_id' => $user->id]);
                            }
                            array_push($addonset, "$addon->id");
                            array_push($licenses, $license);
                        } else {
                            $transaction = $possiblelicense['payment_remote_id'];
                            if (!$transaction) {
                                while (!$transaction or License::where("transaction", '=', $transaction)->first()) {
                                    $bytes = random_bytes(32);
                                    $transaction = "bgx_" . bin2hex($bytes);
                                }
                            }
                            $license = ['blacklisted' => false, "sxcid" => "$userid", 'buyer' => $userid, 'name' => $addon['name'], 'ip' => [], 'maxusage' => 2, 'product_id' => $addon['id'], 'transaction' => $transaction, 'usage' => 0, "buyerid" => 500];
                            if ($user) {
                                array_push($license, ['user_id' => $user->id]);
                            }
                            License::create($license);
                            array_push($addonset, "$addon->id");
                            array_push($licenses, $license);
                        }
                    }
                }
            }
            if (count($licenses) > 0) {
                if (!$user) {
                    Mail::to($user->email)
                        ->send(new TestMail('Bagou450', 'Cloud Servers', $licenses));
                } else {
                    Mail::to($request->userid)
                        ->send(new TestMail('Bagou450', 'Cloud Servers', $licenses));
                }
                Mail::to('receipts@bagou450.com')
                    ->send(new TestMail('Bagou450', 'Cloud Servers', $licenses));
                return response()->json(['status' => 'success'], 200);

            } else {
                return response()->json(['status' => 'error', 'message' => 'A unexcepted error happend.'], 500);
            }
        } else if ($request->type === 'bbb') {
            $headers = ['Authorization' => 'Private PwtlefsFmrs/Clah1rLB2kdd6zvr6UgO', 'Content-Type' => 'application/json'];
            $addonlist = Products::where('licensed', '=', true)->get();
            $licenseFound = false;
            foreach ($addonlist as $addon) {
                $licenseBBB = Http::withHeaders($headers)->get("https://api.builtbybit.com/v1/resources/$addon->bbb_id/licenses/members/$request->userid")->json();
                if ($licenseBBB['result'] == 'success') {
                    $licenseBBB = $licenseBBB['data'];

                    if (License::where('bbb_license', '=', $licenseBBB['license_id'])->where('bbb_id', '=', $addon->bbb_id)->first()) {
                        License::where('bbb_license', '=', (string)$licenseBBB['license_id'])
                            ->where('bbb_id', '=', (string)$addon->bbb_id)
                            ->update(['user_id' => $user->id]);
                        $licenseFound = true;
                    } else {

                        $transaction = '5LB094126U433992N';
                        while (!$transaction or License::where("license", '=', $transaction)->first()) {
                            $bytes = random_bytes(32);
                            $transaction = "bgxb_" . bin2hex($bytes);
                        }

                        $license = ['buyer' => $user->name, 'buyerid' => 500, 'blacklisted' => false, "sxcid" => null, 'product_id' => $addon->id, 'ip' => [], 'maxusage' => 2, 'license' => $transaction, 'usage' => 0, 'bbb_id' => $addon->bbb_id, 'bbb_license' => $licenseBBB['license_id']];
                        if ($user) {
                            $license['user_id'] = $user->id;
                        }

                        License::create($license);
                        $licenseFound = true;
                    }
                }

            }
            if ($licenseFound) {
                return response()->json(['status' => 'success'], 200);

            }
            return response()->json(['status' => 'error', 'message' => 'No license found.'], 500);

        } else {
            return response()->json(['status' => 'error', 'message' => 'A unexcepted error happend.'], 500);

        }


    }

    public function encryptAllIPs()
    {
        $user = auth('sanctum')->user();
        if (!$user || $user->role !== 1) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized!.'], 500);
        }
        $licenses = License::all();

        foreach ($licenses as $license) {
            $encryptedIPs = [];

            foreach ($license->ip as $ip) {
                $encryptedIPs[] = Crypt::encrypt($ip);
            }

            $license->ip = $encryptedIPs;
            $license->save();
        }

        return response()->json(['status' => 'success', 'message' => 'All IPs encrypted successfully'], 200);
    }

    /**
     *
     * Reset a license
     * @return \Illuminate\Http\JsonResponse
     */
    public function resetLicense(License $license)
    {
        $user = auth('sanctum')->user();
        if (!$user || ($license->user_id !== $user->id && $user->role !== 1)) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized!'], 500);
        }
        $license->usage = 0;
        $license->ip = [];
        $license->save();
        return response()->json(['status' => 'success', 'message' => 'License was reseted successfully'], 200);
    }

    /**
     *
     * Link License to a account
     * @return \Illuminate\Http\JsonResponse
     */
    public function licenseLink(string $license)
    {
        $user = auth('sanctum')->user();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized!'], 500);
        }
        $license = License::where('license', $license)->first();
        if (!$license) {
            return response()->json(['status' => 'error', 'message' => 'License not found!'], 404);
        }
        if ($license->user_id) {
            if ($license->user_id === $user->id) {
                return response()->json(['status' => 'error', 'message' => 'You already own this license!'], 500);

            }
            return response()->json(['status' => 'error', 'message' => 'License already linked to another account!'], 500);
        }
        $license->user_id = $user->id;
        $license->save();
        return response()->json(['status' => 'success', 'message' => 'License added successfully!'], 200);

    }
}


