<?php

namespace App\Http\Controllers\Api\Client\Web\License;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Crypt;

class LicenseResource extends JsonResource
{
   public function toArray($request)
    {
        $ips = [];
        foreach($this->ip as $ip) {
            $ips[] = Crypt::decrypt($ip);
        }

        return [
            'product_id' => $this->product_id,
            'ip' => $ips,
            'maxusage' => $this->maxusage,
            'license' => $this->license,
            'usage' => $this->usage,
            'version' => $this->version,
            'order_id' => $this->order_id,
        ];
    }
}