<?php

namespace App\Http\Controllers\Api\Client\Pterodactyl;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\File;
use App\Models\License;

class McVersionsController extends BaseController
{
    public function getVersions(Request $request)
    {

         $this->validate($request, [
                'id' => 'required',
                'stype' => 'required',
         ]);

        $license = $this->checkLicense($request->id, 296, $request->ip());

        if ($license->getStatusCode() === 200) {
            $versions = null;

            $validStypes = [
                'modpacks', 'others'
            ];

            if (!in_array($request->stype, $validStypes)) {
                $maj = ucfirst($request->stype);
                $versions = Http::get("https://cdn.bagou450.com/versions/minecraft/getlist/$maj.json")->json();
            } elseif ($request->stype === 'modpacks') {
                $maj = ucfirst($request->modpacktype);
                $versions = Http::get("https://cdn.bagou450.com/versions/minecraft/getlist/modpacks/$maj.json")->json();
            } elseif ($request->stype === 'others') {
                $versions = Http::get("http://$request->url/versionlist.json")->json();
            } else {
                return response()->json([
                    'message' => 'Stype is invalid.'
                ], 400);
            }

            if ($versions) {
                return response()->json([
                    'message' => 'Good',
                    'data' => $versions
                ], 200);
            } else {
                return response()->json([
                    'message' => 'Unable to fetch versions.'
                ], 400);
            }
        } else {
            return $license;
        }
    }

    public function downloadVersion(Request $request)
    {
        $this->validate($request, [
                'id' => 'required',
                'stype' => 'required',
                'version' => 'required',
            ]);
        $license = $this->checkLicense($request->id, 296, $request->ip());

        if ($license->getStatusCode() === 200) {
            $url = '';

            $nozipStypes = [
                'bungeecord', 'velocity', 'waterfall', 'flamecord', 'mohist', 'catserver', 'sponge', 'paper', 'magma',
                'spigot', 'purpur', 'tuinity', 'sponge', 'vanilla', 'snapshot'
            ];
            $zipStypes = [
                'modpacks', 'forge', 'fabric'
            ];
            if (in_array($request->stype, $nozipStypes)) {
                $url = "https://cdn.bagou450.com/versions/minecraft/$request->stype/$request->version";
            } elseif (in_array($request->stype, $zipStypes)) {
                $url = "https://cdn.bagou450.com/versions/minecraft/$request->stype/$request->version.zip";

            } elseif ($request->stype === 'others') {
                $url = "http://$request->url";
            } else {
                return response()->json([
                    'message' => 'Stype is invalid.'
                ], 400);
            }

            if ($url !== '') {
                $url = str_replace(' ', '%20', $url);
                $headers = get_headers($url, true);
                $contentLength = isset($headers['Content-Length']) ? $headers['Content-Length'] : null;

                return response()->json([
                    'message' => 'Good',
                    'data' => $url,
                    'size' => $contentLength,
                    'version' => floatval(json_decode($license->getContent())->version)
                ], 200);
            } else {
                return response()->json([
                    'message' => 'Unable to fetch version download URL.'
                ], 400);
            }
        } else {
            return $license;
        }
    }
}
