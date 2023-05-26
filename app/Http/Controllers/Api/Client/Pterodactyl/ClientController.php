<?php

namespace App\Http\Controllers\Api\Client\Pterodactyl;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\License;
use App\Models\Products;
use Config;
class ClientController extends BaseController
{

    public function checkLicense(string $transaction, string $id, string $ip) {
        return response()->json([
            'message' => 'Good.'
        ], 200);
        $license = License::where("transaction", '=', $transaction)->first();
        if (!$license) {
           // return true;
            return response()->json([
                'message' => 'License not found.'
            ], 418);;
        } else {
            $key = config('api.key');

            if($license->blacklisted) {
                return response()->json([
                    'message' => 'User blacklisted.'
                ], 418);
            }
            

    
                if($id !== $license->name) {
                    return response()->json([
                        'message' => 'Not the good addon.'
                    ], 400);
                }
                if(!in_array($ip,$license->ip)) {
                    return response()->json([
                        'message' => 'This ip is not allowed.'
                    ], 400);
                }
                return response()->json([
                    'message' => 'Good.'
                ], 200);
            
        }
    }
    public function getLicense(Request $request) {
        $addon = License::where("transaction", '=', $request->id)->first();
        if (!$addon) {
            return $this->addtodb($request);
        } else {
            $key = config('api.key');

            if($addon->blacklisted) {
                return response()->json([
                    'message' => 'User blacklisted.'
                ], 418);
            }
           
                if($request->selectaddon !== strval($addon->name)) {
                    return response()->json([
                        'message' => 'Not the good addon.'
                    ], 400);
                }
                if($addon->usage+1 > $addon->maxusage) {
                    return response()->json([
                        'message' => 'Too many usage.'
                    ], 400);
                }
                
                License::where("transaction", '=', $request->id)->update(["usage" => $addon->usage+1, "version" => Products::where('id', '=', $addon->name)->firstOrFail()['version'], 'ip' => array_push(License::where("transaction", '=', $request->id)->firstOrFail()['ip'], [$request->ip()])]);
                
                return array("message" => "done", "name" => $addon->name, "fullname" => $addon->fullname, "buyer" => $addon->buyer, "transaction" => $addon->transaction, "blacklisted" => $addon->blacklisted, "usage" => $addon->usage, "maxusage" => $addon->maxusage, "ip" => $addon->ip, "version" => Products::where('id', '=', $addon->name)->firstOrFail()['version']);
            
        }
        
    }
    public function getVersion(Request $request) {
        $addon = License::where("transaction", '=', $request->id)->first();
        if (!$addon) {
            return response()->json([
                'message' => 'Unknow license.'
            ], 400);
        } else {
            $version = Products::where('id', '=', $addon->name)->firstOrFail();
            return array("version" => $version['version']);
            
        }
    }
    public function deleteLicense(Request $request) {
        $addon = License::where("transaction", '=', $request->id)->first();
        if (!$addon) {
            return response()->json([
                'message' => 'Products not found.'
            ], 400);
        } 
        if ($addon->usage < 1) {
            return response()->json([
                'message' => 'Products not used.'
            ], 400);
        } 
        License::where("transaction", '=', $request->id)->update(["usage" => $addon->usage-1]);
        return response()->json([
            'message' => 'Done.'
        ], 200);
    }
    public function getAutoInstaller(Request $request) {

        $addon = License::where("transaction", '=', $request->id)->first();
        if (!$addon) {
            return $this->addtodb($request);
        } else {
            $key = config('api.key');

            if($addon->blacklisted) {
                return response()->json([
                    'message' => 'User blacklisted.'
                ], 418);
            }
            $user = Http::withHeaders(['Authorization' => $key, "userid" => $addon->buyerid])
            ->post('https://pterodactylmarket.com/api/seller/user')
            ->json();
            if($user['banned']) {
                License::where("transaction", '=', $request->id)->update(["blacklisted" => true]);
                return response()->json([
                    'message' => 'User blacklisted.'
                ], 418);
            } else {
                if($request->selectaddon !== strval($addon->name)) {
                    return response()->json([
                        'message' => 'Not the good addon.'
                    ], 400);
                }
                if($addon->usage+1 > $addon->maxusage) {
                    return response()->json([
                        'message' => 'Too many usage.'
                    ], 400);
                }
                return json_decode(file_get_contents("../autoinstaller/$request->selectaddon.json"), true);
            }
        }
    }
    public function checkOnline() {
        return 1;
    }
    public function addonsList() {
        $addon = Products::where('licensed', '=', 1)->get();
        $addonarray = array();
        foreach($addon as $add) {
            array_push($addonarray, $add);
        }
        return $addonarray;
    }

    public function checkIfExist(Request $request) {
        $addon = License::where("transaction", '=', $request->id)->first();
        if (!$addon) {
            $key = config('api.key');
        $response = Http::withHeaders(['Authorization' => $key, 'transactionid' => $request->id])
        ->post('https://pterodactylmarket.com/api/seller/transaction?Authorization')
        ->json();
        if($response['valid'] && $response['transaction_status'] == "COMPLETE") {
            $addon = Http::withHeaders(['resourceid' => $response['resource_id'], "query" => "info"])
            ->post('https://pterodactylmarket.com/api/public/resource')
            ->json();
            $user = Http::withHeaders(['Authorization' => $key, "userid" => $response['buyer_id']])
            ->post('https://pterodactylmarket.com/api/seller/user')
            ->json();
            if($user['banned']) {
                return response()->json([
                    'message' => 'User blacklisted.'
                ], 418);
            } else {
                return response()->json([
                    "message" => "done", "name" => $response['resource_id'], "fullname" => $addon['name'], "buyer" => $user['username']
                                ], 200);
            }
        } else {
            return response()->json([
                'message' => 'Transaction is not valid.'
            ], 400);
        }
        } else {
            $key = config('api.key');

            if($addon->blacklisted) {
                return response()->json([
                    'message' => 'User blacklisted.'
                ], 418);
            }
            $user = Http::withHeaders(['Authorization' => $key, "userid" => $addon->buyerid])
            ->post('https://pterodactylmarket.com/api/seller/user')
            ->json();
            if($user['banned']) {
  License::where("transaction", '=', $request->id)->update(["blacklisted" => true]);
                return response()->json([
                    'message' => 'User blacklisted.'
                ], 418);
            } else {
                if($addon->usage+1 > $addon->maxusage) {
                    return response()->json([
                        'message' => 'Too many usage.'
                    ], 400);
                }

                return response()->json([
                    "message" => "done", "name" => $addon->name, "fullname" => $addon->fullname, "blacklisted" => $addon->blacklisted
                ], 200);
            }
        }
        
    }
    public function checklicenseCloudServers(Request $request) {
        $addon = License::where("transaction", '=', $request->id)->first();
        if (!$addon) {
            return response()->json([
                'message' => 'Can\'t find the transaction!'
            ], 400);
        } else {
            $key = config('api.key');

            if($addon->blacklisted) {
                return response()->json([
                    'message' => 'User blacklisted.'
                ], 418);
            }
            $user = Http::withHeaders(['Authorization' => $key, "userid" => $addon->buyerid])
                ->post('https://pterodactylmarket.com/api/seller/user')
                ->json();
            if($user['banned']) {
                License::where("transaction", '=', $request->id)->update(["blacklisted" => true]);
                return response()->json([
                    'message' => 'User blacklisted.'
                ], 418);
            } else {

                if('585' !== strval($addon->name)) {
                    return response()->json([
                        'message' => 'Not the good addon.'
                    ], 400);
                }
                return array('message' => 'done', 'name' => '585', 'fullname' => 'Cloud Servers', 'blacklisted' => false);
            }
        }
    }
}
