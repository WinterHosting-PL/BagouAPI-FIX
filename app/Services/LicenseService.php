<?php

namespace App\Services;

use App\Models\License;
use App\Models\Products;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LicenseService
{
    const LICENSE_NOT_FOUND = 'LICENSE_NOT_FOUND';
    const BLACKLISTED = 'BLACKLISTED';
    const NO_ADDON = 'NO_ADDON';
    const IP_NOT_ALLOWED = 'IP_NOT_ALLOWED';
    const TOO_MANY_USAGE = 'TOO_MANY_USAGE';
    const SUCCESS = 'SUCCESS';
    const USAGE_CANNOT_DECREMENT = 'USAGE_CANNOT_DECREMENT';
    const USAGE_DECREMENTED = 'USAGE_DECREMENTED';

    public function checkLicense(string $licenseProvided ,string $id ,string $ip ,bool $ipcheck)
    {
        $license = License::where("license" ,'=' ,$licenseProvided)->first();
        if (!$license) {
            return self::LICENSE_NOT_FOUND;
        } else {
            if ($license->blacklisted) {
                return self::BLACKLISTED;
            }

            if ($id !== $license->name) {
                return self::NO_ADDON;
            }
            $ips = [];
            foreach ($license->ip as $encryptedIp) {
                $ips[] = Crypt::decrypt($encryptedIp);
            }
            if (!in_array($ip ,$ips)) {
                return self::IP_NOT_ALLOWED;
            }

            return self::SUCCESS;

        }
    }

    public function getDetails(string $id ,string $addonId ,string $ip ,bool $ipcheck ,bool $addoncheck)
    {

        $license = License::where("license" ,'=' ,$id)->first();

        if (!$license) {
            return self::LICENSE_NOT_FOUND;
        } else {
            if ($license->blacklisted) {
                return self::BLACKLISTED;
            }

            if ($addonId !== $license->product_id && $addoncheck) {
                return self::NO_ADDON;
            }
            $ips = [];
            foreach ($license->ip as $encryptedIp) {
                $ips[] = Crypt::decrypt($encryptedIp);
            }
            if (!in_array($ip ,$ips) && $ipcheck) {
                return self::IP_NOT_ALLOWED;
            }

            if ($license->usage + 1 > $license->maxusage) {
                return self::TOO_MANY_USAGE;
            }

            return $license;

        }

    }

    public function incrementUsage(string $license ,string $ip)
    {
        $license = License::where('license' ,$license)->first();
        if ($license) {
            $ips = [];
            foreach ($license->ip as $encryptedIp) {
                $ips[] = Crypt::decrypt($encryptedIp);
            }
            if(in_array($ip ,$ips)) {
                $license->version = Products::where('id' ,'=' ,$license->name)->firstOrFail()['version'];
                $license->save();
                return true;
            }
            if ($license->usage + 1 > $license->maxusage) {
                return self::TOO_MANY_USAGE;
            }

            try {
                $license->usage += 1;
                $license->ip = array_merge($license->ip ,[Crypt::encrypt($ip)]);
                $license->version = Products::where('id' ,'=' ,$license->name)->firstOrFail()['version'];
                $license->save();

                return true;
            } catch (\Exception $e) {
                Log::error("A error occurred: " . $e->getMessage());
                return false;
            }
        }

        return self::LICENSE_NOT_FOUND;
    }

    public function getVersion(string $license)
    {
        $addon = License::where("license" ,'=' ,$license)->first();
        if (!$addon) {
            return null;
        } else {
            $version = Products::where('id' ,'=' ,$addon->name)->firstOrFail();
            return $version['version'];
        }
    }

    public function decrementUsage(string $license, string $ip)
    {
        $license = License::where('license' ,$license)->first();
        if ($license) {
            if ($license->usage < 1) {
                return self::USAGE_CANNOT_DECREMENT;
            }
            $ips = [];
            foreach ($license->ip as $encryptedIp) {
                $ips[] = Crypt::decrypt($encryptedIp);
            }
            if (!in_array($ip ,$ips)) {
                return self::IP_NOT_ALLOWED;
            }
            try {
                $license->usage -= 1;
                $ips = [];
                foreach($license->ip as $encryptedIp) {
                    if(Crypt::decrypt($encryptedIp) !== $ip) {
                        $ips[] = $encryptedIp;
                    }
                }
                $license->ip = $ips;
                $license->save();
                return self::USAGE_DECREMENTED;
            } catch (\Exception $e) {
                Log::error('A error occurred: ' . $e->getMessage());
                return false;
            }
        }

        return self::LICENSE_NOT_FOUND;
    }

    public function getLicensedAddons()
    {
        return Products::where('licensed' ,'=' ,1)->get();
    }
}
