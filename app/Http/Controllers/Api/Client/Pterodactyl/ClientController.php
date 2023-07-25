<?php

namespace App\Http\Controllers\Api\Client\Pterodactyl;

use App\Services\LicenseService;
use Illuminate\Http\File;
use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\License;
use App\Models\Products;

use Config;
use Illuminate\Support\Facades\Validator;

class ClientController extends Controller
{
    protected $licenseService;

    public function __construct(LicenseService $licenseService)
    {
        $this->licenseService = $licenseService;

    }


    public function checkLicense(string $transaction , string $id , string $ip)
    {

        $result = $this->licenseService->checkLicense($transaction , $id , $ip , true);
        if ($result === 'SUCCESS') {
            return [
                'message' => 'done' ,
                'name' => '585' ,
                'fullname' => 'Cloud Servers' ,
                'blacklisted' => false
            ];
        } else {
            return response()->json([
                'message' => $result
            ] , 400);
        };
    }

    public function getLicense(Request $request)
    {
        $validator = Validator::make($request->all() , [
            'id' => 'required' ,
            'name' => 'required' ,
        ]);
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Invalid data provided.' ,
                'errors' => $validator->errors() ,
            ] , 400);
        }
        $result = $this->licenseService->getDetails($request->id , $request->name , $request->ip() , false , true);
        switch ($result) {
            case LicenseService::LICENSE_NOT_FOUND:
                return response()->json([
                    'status' => 'error' ,
                    'message' => 'No license found.'
                ] , 404);

            case LicenseService::BLACKLISTED:
                return response()->json([
                    'status' => 'error' ,
                    'message' => 'User blacklisted.'
                ] , 404);

            case LicenseService::NO_ADDON:
                return response()->json([
                    'status' => 'error' ,
                    'message' => 'Not the good addon.'
                ] , 404);

            case LicenseService::TOO_MANY_USAGE:
                return response()->json([
                    'status' => 'error' ,
                    'message' => 'Too many usage.'
                ] , 400);

            default:
                if ($this->licenseService->incrementUsage($request->id , $request->ip())) {
                    return array(
                        'status' => 'success' ,
                        'message' => 'done' ,
                        'name' => $result->product_id ,
                        'license' => $result->license ,
                        'blacklisted' => $result->blacklisted ,
                        'usage' => $result->usage ,
                        'maxusage' => $result->maxusage ,
                        'version' => Products::where('id' , '=' , $result->name)->firstOrFail()['version']
                    );
                }

                return response()->json([
                    'status' => 'error' ,
                    'message' => 'Can\'t add the usage.'
                ] , 400);
        }
    }

    public function getVersion(Request $request)
    {
        $validator = Validator::make($request->all() , [
            'id' => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Invalid data provided.' ,
                'errors' => $validator->errors() ,
            ] , 400);
        }
        $version = $this->licenseService->getVersion($request->id);
        if ($version === null) {
            return response()->json([
                'status' => 'error' ,
                'message' => 'Unknown license.'
            ] , 404);
        } else {
            return response()->json([
                'status' => 'success' ,
                'version' => $version
            ] , 200);
        }
    }

    public function deleteLicense(Request $request)
    {
        $validator = Validator::make($request->all() , [
            'id' => 'required' ,
        ]);
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Invalid data provided.' ,
                'errors' => $validator->errors() ,
            ] , 400);
        }
        $result = $this->licenseService->decrementUsage($request->id, $request->ip);
        if ($result === LicenseService::LICENSE_NOT_FOUND) {
            return response()->json([
                'status' => 'error' ,
                'message' => 'License not found.'
            ] , 404);
        } elseif ($result === LicenseService::USAGE_CANNOT_DECREMENT) {
            return response()->json([
                'status' => 'error' ,
                'message' => 'Cannot decrement license usage.'
            ] , 400);
        } elseif ($result === LicenseService::IP_NOT_ALLOWED) {
            return response()->json([
                'status' => 'error' ,
                'message' => 'This ip is not allowed to use the license..'
            ] , 400);
        } elseif ($result === LicenseService::USAGE_DECREMENTED) {
            return response()->json([
                'status' => 'success' ,
                'message' => 'License usage decremented successfully.'
            ] , 200);
        }
    }

    public function getAutoInstaller(Request $request)
    {
        $validator = Validator::make($request->all() , [
            'id' => 'required' ,
            'selectaddon' => 'required' ,
        ]);
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Invalid data provided.' ,
                'errors' => $validator->errors() ,
            ] , 400);
        }
        $result = $this->licenseService->checkLicense($request->id , $request->selectaddon , $request->ip() , false);
        if ($result === 'SUCCESS') {
            $autoInstallerPath = storage_path("../autoinstaller/$request->selectaddon.json");

            if (File::exists($autoInstallerPath)) {
                $autoInstallerContent = File::get($autoInstallerPath);
                $autoInstallerData = json_decode($autoInstallerContent , true);

                return $autoInstallerData;
            } else {
                return response()->json([
                    'message' => 'Auto Installer file not found.' ,
                ] , 404);
            }
        } else {
            return response()->json([
                'message' => $result
            ] , 400);
        }
    }

    public function checkOnline()
    {
        return 1;
    }

    public function addonsList()
    {
        $licensedAddons = $this->licenseService->getLicensedAddons();
        return $licensedAddons->toArray();
    }

    public function checkIfExist(Request $request)
    {
        $validator = Validator::make($request->all() , [
            'id' => 'required' ,
        ]);
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Invalid data provided.' ,
                'errors' => $validator->errors() ,
            ] , 400);
        }
        $result = $this->licenseService->getDetails($request->id , '' , $request->ip() , false , false);
        switch ($result) {
            case LicenseService::LICENSE_NOT_FOUND:
                return response()->json([
                    'status' => 'error' ,
                    'message' => 'No license found.'
                ] , 404);

            case LicenseService::BLACKLISTED:
                return response()->json([
                    'status' => 'error' ,
                    'message' => 'User blacklisted.'
                ] , 404);

            case LicenseService::TOO_MANY_USAGE:
                return response()->json([
                    'status' => 'error' ,
                    'message' => 'Too many usage.'
                ] , 400);

            default:

                return response()->json([
                    'status' => 'success' ,
                    "message" => "done" , "name" => $result->product_id , "buyer" => $result->user->id
                ] , 200);
        }


    }

}
