<?php

namespace App\Http\Controllers\Api\Client\Web\Admin\Licenses;

use App\Models\License;
use App\Models\Products;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class LicensesController
{
    /**
     * Liste des licenses.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function listLicenses(Request $request)
    {
        $user = auth('sanctum')->user();

        if (!$user || $user->role !== 1) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }
        $page = $request->input('page', 1);
        $perPage = $request->input('perpage', 10);
        $search = $request->input('search', '');
        $licensesQuery = License::query();

        if ($search) {
            $licensesQuery->where(function ($query) use ($search) {
                $query->where('ip', 'LIKE', '%' . $search . '%')
                    ->orWhere('license', 'LIKE', '%' . $search . '%')
                    ->orWhere('user_id', 'LIKE', '%' . $search . '%');
            });
        }
        $total = ceil($licensesQuery->count()/$perPage);
        $licenses = $licensesQuery->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();
        $licenses = $licenses->map(function ($license) {
            $licenseData = $license->toArray();
            $decryptedIp = array_map(function ($encryptedIp) {
                return Crypt::decrypt($encryptedIp);
            }, $licenseData['ip']);
            $licenseData['ip'] = $decryptedIp;
            $licenseData['product_name'] = $license->product->name;
            unset($licenseData['product']);
            return $licenseData;
        });
        return response()->json(['status' => 'success', 'data' => $licenses, 'total' => $total]);
    }

    /**
     *
     * Reset une license
     * @return \Illuminate\Http\JsonResponse
     */
    public function resetLicense(License $license) {
        $user = auth('sanctum')->user();

        if (!$user || $user->role !== 1) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }
        $license->usage = 0;
        $license->ip = [];
        $license->save();
        return response()->json(['status' => 'success', 'message' => 'License reseted sucessfully']);

    }

    /**
     *
     * Blacklist License
     * @return \Illuminate\Http\JsonResponse
     */
    public function licenseBlacklist(License $license) {
        $user = auth('sanctum')->user();

        if (!$user || $user->role !== 1) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }
        $license->usage = $license->maxusage;
        if($license->blacklisted) {
            $license->usage = 0;
            $license->ip = [];
        }
        $license->blacklisted = !$license->blacklisted;
        $license->save();
        return response()->json(['status' => 'success', 'message' => 'License blacklisted sucessfully']);

    }

}