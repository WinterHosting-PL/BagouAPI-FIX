<?php

namespace App\Services;

use App\Models\ModsVersionsResult;
use Illuminate\Support\Facades\Http;

class ModsVersionsService
{
    public function getCurseForgeModVersions(string $modId, int $page)
    {
        $cursekey = config('api.cursekey');
        $headers = ['User-Agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/92.0.4515.81 Safari/537.36', 'x-api-key' => $cursekey];
        $index = $page * 10 - 10;

        return Http::accept('application/json')->withHeaders($headers)->get("https://api.curseforge.com/v1/mods/$modId/files?index=$index&pageSize=10")->object()->data;
    }

    public function getModrinthModVersions(string $modId, int $page)
    {
        $headers = ['User-Agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/92.0.4515.81 Safari/537.36'];
        $addons = Http::accept('application/json')->withHeaders($headers)->get("https://api.modrinth.com/v2/project/$modId/version")->json();
        $addons = array_slice($addons, $page * 10 - 10, 10);

        return $addons;
    }

    public function getModVersionsFromCache(int $page, string $modId, string $type)
    {
        $data = ModsVersionsResult::where(['page' => $page, 'modid' => $modId, 'type' => $type])->first();

        if ($data && $data->updated_at->diffInHours(now()) < 24) {
            return $data->result;
        }

        return null;
    }

    public function cacheModVersions(int $page, string $modId, string $type, array $versions)
    {
        ModsVersionsResult::where('page', '=', $page)->where('type', '=', $type)->delete();
        ModsVersionsResult::create(['page' => $page, 'modid' => $modId, 'result' => json_encode($versions), 'type' => $type]);
    }
}
