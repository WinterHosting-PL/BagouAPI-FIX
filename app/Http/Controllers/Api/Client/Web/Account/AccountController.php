<?php
 
namespace App\Http\Controllers\Api\Client\Web\Account;
 
use App\Models\Ticket;
use App\Models\UserDiscord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Routing\Controller;
use App\Models\User;
use App\Http\Requests\LoginRequest;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Mail;
use App\Mail\LoginEmail;
use Laravel\Passport\Guards\TokenGuard;
use Laravel\Passport\Token;
use Laravel\Passport\PersonalAccessToken;
use Illuminate\Support\Facades\Hash;
use Illuminate\Contracts\Hashing\Hasher;

class AccountController extends Controller
{
    public function edit(Request $request): \Illuminate\Http\JsonResponse {
        $user = auth('sanctum')->user();
        User::where('id', '=', $user->id)->update(['email' => $request->email]);

        return response()->json(['status' => 'success', 'message' => 'Account credentials successfully updated'], 200);
    }
    public function editinfos(Request $request): \Illuminate\Http\JsonResponse {
        $user = auth('sanctum')->user();
        
        User::where('id', '=', $user->id)->update([
        'society' => $request->society, 
        'address' => $request->address, 
        'city' => $request->city, 
        'region' => $request->region, 
        'country' => $request->country,
        'postal_code' => $request->postalcode,
        'firstname' => $request->firstname,
        'lastname' => $request->lastname]);
        return response()->json(['status' => 'success', 'message' => 'Account informations successfully updated'], 200);

    }
    public function getinfos(): \Illuminate\Http\JsonResponse {
        $user = auth('sanctum')->user();
        if(!$user) {
            return response()->json(['status' => 'error', 'message' => 'You need to be logged.'], 500);
        }
        return response()->json(['status' => 'success', 'data' => [
            'society' => $user->society, 
            'address' => $user->address, 
            'city' => $user->city, 
            'region' => $user->region, 
            'country' => $user->country,
            'postal_code' => $user->postal_code,
            'firstname' => $user->firstname,
            'lastname' => $user->lastname,
        ]], 200);

       
    }

    public function discordLogin() {
        $user = auth('sanctum')->user();
        if(!$user) {
            return response()->json(['status' => 'error', 'message' => 'You need to be logged.'], 500);
        }
        $clientId = config('services.discord.client');;
        $redirectUri = config('services.discord.redirection');
        $scopes = 'identify guilds email guilds.join'; // Les scopes d'autorisation nécessaires, séparés par des espaces

        $discordAuthorizeUrl = 'https://discord.com/api/oauth2/authorize';
        $queryParams = http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => $scopes,
        ]);
        return response()->json(['status' => 'success', 'data' => ['url' => "$discordAuthorizeUrl?$queryParams"]]);
    }
    public function discordCallback(Request $request)
    {
        $user = auth('sanctum')->user();
        if(!$user) {
            return response()->json(['status' => 'error', 'message' => 'You need to be logged.'], 500);
        }
        $clientId = config('services.discord.client');;
        $clientSecret = config('services.discord.client_secret');
        $redirectUri = config('services.discord.redirection');
        $code = $request->query('code');
        $scopes = 'identify guilds email guilds.join'; // Les scopes d'autorisation nécessaires, séparés par des espaces
        $discordTokenUrl = 'https://discord.com/api/oauth2/token';
        $data = [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'redirect_uri' => $redirectUri,
            'scope' => $scopes, // Les scopes doivent correspondre à ceux utilisés lors de l'autorisation
        ];
        $response = Http::asForm()->post($discordTokenUrl, $data);
        if ($response->successful()) {
            $accessToken = $response->json('access_token');

            // Utilisez l'access token pour récupérer les informations utilisateur
            $discordUserUrl = 'https://discord.com/api/users/@me';
            $userInfoResponse = Http::withToken($accessToken)->get($discordUserUrl);
// Rejoindre le serveur Discord
           // $inviteCode = 'Qjx7MJNZGz'; // Remplacez par le code d'invitation du serveur Discord

            if ($userInfoResponse->successful()) {
                $userInfo = $userInfoResponse->json();
                $guild_ID = config('services.discord.server');
                $discord_ID = $userInfo['id'];
                $joinServerUrl = "https://discordapp.com/api/guilds/$guild_ID/members/$discord_ID";
                $discordtoken = config('services.discord.token');
                $data = [
                    "access_token" => $accessToken
                ];
                $join = Http::withHeaders([
                    'Authorization' => "Bot $discordtoken",
                    'Content-Type' => 'application/json'
                ])->put($joinServerUrl, $data);
                if ($join->successful()) {
                    $userDiscord = new UserDiscord();
                    $userDiscord->discord_id = $userInfo['id'];
                    $userDiscord->user_id = $user->id;
                    $userDiscord->username = $userInfo['username'];
                    $userDiscord->avatar = $userInfo['avatar'];
                    $userDiscord->discriminator = $userInfo['discriminator'];
                    $userDiscord->email = $userInfo['email'];

                    $userDiscord->save();
                    Ticket::where('discord_user_id', $userInfo['id'])->update(['user_id' => $user->id]);
                    return response()->json(['status' => 'success']);

                }
            }


        }

        return response()->json(['error' => 'Unable to retrieve user information from Discord'], 500);
    }
    public function getDiscordUser(Request $request) {
        $user = auth('sanctum')->user();
        if(!$user) {
            return response()->json(['status' => 'error', 'message' => 'Unauthenticated'], 500);
        }
        $discordUser = $user->discord;
        $avatarLink = "https://cdn.discordapp.com/avatars/$discordUser->discord_id/$discordUser->avatar";


        return response()->json(['status' => 'success', 'data' => [
            'username' => $discordUser->username,
            'discriminator' => $discordUser->discriminator,
            'avatar' => $avatarLink
        ]]);

    }
}