<?php
 
namespace App\Http\Controllers\Api\Client\Web\Account;
 
use App\Models\Ticket;
use App\Models\UserDiscord;
use App\Models\UserGitHub;
use App\Models\UserGoogle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
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
        $validator = Validator::make($request->all(), [
            'type' => 'string',
            'data' => 'string'
        ]);
         if ($validator->fails()) {
            $errors = $validator->errors();
            $firstError = $errors->first();
            return response()->json(['status' => 'error', 'message' => $firstError], 500);
        }
         if($request->type === 'email') {
             User::where('id', '=', $user->id)->update(['email' => $request->data]);
         } else if ($request->type === 'name') {
             User::where('id', '=', $user->id)->update(['name' => $request->data]);
         } else {
             return response()->json(['status' => 'error', 'message' => 'Can\'t find a value to edit'], 404);
         }

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
     public function oauthlogin(Request $request)
    {
        $user = auth('sanctum')->user();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'You need to be logged!.'], 500);
        }
        $response = '';
        $redirectUri = config('services.discord.accountredirect') . "?type=$request->type";
        if ($request->type === 'discord') {
            $clientId = config('services.discord.client');;
            $scopes = 'identify email';
            $discordAuthorizeUrl = 'https://discord.com/api/oauth2/authorize';
            $queryParams = http_build_query([
                'client_id' => $clientId,
                'redirect_uri' => $redirectUri,
                'response_type' => 'code',
                'scope' => $scopes,
            ]);
            $response = "$discordAuthorizeUrl?$queryParams";
        }else if ($request->type === 'google') {
            $response = 'https://accounts.google.com/o/oauth2/auth' .
                '?client_id=' . config('services.google.client_id') .
                '&redirect_uri=' . $redirectUri .
                '&response_type=code' .
                '&scope=email%20profile';

        }else if ($request->type === 'github') {
            $response = 'https://github.com/login/oauth/authorize' .
                '?client_id=' . config('services.github.client_link_id') .
                '&redirect_uri=' . $redirectUri .
                '&scope=read:user%20user:email';
        }

        return response()->json(['status' => 'success', 'data' => ['url' => "$response"]]);
    }
     public function oauthloginCallback(Request $request)
    {
        $user = auth('sanctum')->user();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'You need to be logged!.'], 500);
        }
                    $redirectUri = config('services.discord.accountredirect') . "?type=$request->type";

               if ($request->type === 'discord') {
            $clientId = config('services.discord.client');;
            $clientSecret = config('services.discord.client_secret');
            $code = $request->token;
            $scopes = 'identify email';
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
                if ($userInfoResponse->successful()) {

                    $userInfo = $userInfoResponse->json();
                    $discordId = $userInfo['id'];
                    $userDiscord = UserDiscord::where('discord_id', $discordId)->first();
                    if ($userDiscord) {
                        //Compte Discord déja liée.
                        return response()->json(['status' => 'error', 'message' => 'This Discord account is already link!'], 500);
                    } else {
                        $discriminator =  $userInfo['discriminator'];
                        $newDiscorduser = new UserDiscord();
                        $newDiscorduser->discord_id = "$discordId";
                        $newDiscorduser->username = $userInfo['username'];
                        $newDiscorduser->email = $userInfo['email'];
                        $newDiscorduser->discriminator = "$discriminator";
                        $newDiscorduser->user_id = $user->id;
                        $newDiscorduser->avatar = $userInfo['avatar'];
                        $newDiscorduser->save();
                        return response()->json(['status' => 'success']);
                    }
                }
            }
            return response()->json(['status' => 'error', 'message' => 'Unable to retrieve user information from Discord'], 500);

        }
        if ($request->type === 'google') {
            $response = Http::post('https://oauth2.googleapis.com/token', [
                'code' => $request->token,
                'client_id' => config('services.google.client_id'),
                'client_secret' => config('services.google.client_secret'),
                'redirect_uri' => $redirectUri,
                'grant_type' => 'authorization_code',
            ])->object();
            if (isset($response->error) || !isset($response->access_token)) {
                return response()->json(['status' => 'error', 'message' => 'An unexcepted error happend.'], 500);
            }

            $accessToken = $response->access_token;
            $profile = Http::get('https://www.googleapis.com/oauth2/v2/userinfo', [
                'access_token' => $accessToken,
            ])->object();
            $userGoogle = UserGoogle::where('google_id', "$profile->id")->first();
            if ($userGoogle) {
                return response()->json(['status' => 'error', 'message' => 'This Google account is already link!'], 500);
            }else {
                $newGoogleuser = new UserGoogle();
                $newGoogleuser->google_id = "$profile->id";
                $newGoogleuser->user_id = $user->id;
                $newGoogleuser->username = $profile->name;
                $newGoogleuser->avatar = $profile->picture;
                $newGoogleuser->save();
                return response()->json([
                    'status' => 'success'
                ]);
            }
        }
        if ($request->type === 'github') {
            $response = Http::withHeaders(['Accept' => 'application/json'])->post('https://github.com/login/oauth/access_token', [
                'code' => $request->token,
                'client_id' => config('services.github.client_link_id'),
                'client_secret' => config('services.github.client_link_secret'),
                'redirect_uri' => $redirectUri
            ])->object();

            if (!isset($response->access_token)) {
                return response()->json(['status' => 'error', 'message' => 'An unexcepted error happend.'], 500);
            }
            $accessToken = $response->access_token;
            $profile = Http::withHeaders([
                'Authorization' => "Bearer $response->access_token",
            ])->get('https://api.github.com/user')->object();
            if (!isset($profile->id)) {
                return response()->json(['status' => 'error', 'message' => 'An unexcepted error happend.'], 500);
            }
            $userGithub = UserGitHub::where('github_id', "$profile->id")->first();
            if ($userGithub) {
                return response()->json(['status' => 'error', 'message' => 'This Github account is already link!'], 500);
            }
            $newGithubuser = new UserGitHub();
            $newGithubuser->github_id = "$profile->id";
            $newGithubuser->user_id = $user->id;
            $newGithubuser->username = $profile->login;
            $newGithubuser->avatar = $profile->avatar_url;
            $newGithubuser->plan = $profile->plan->name;
            $newGithubuser->save();
             return response()->json([
                    'status' => 'success'
                ]);
        }

        return response()->json(['status' => 'error', 'message' => 'Unable to know what type of Oauth use.'], 500);

    }
    public function deleteOauthLogin(Request $request) {
        $user = auth('sanctum')->user();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'You need to be logged!.'], 500);
        }
        if(!$request->type) {
            return response()->json(['status' => 'error', 'message' => 'You need to provide a Oauth type!.'], 500);
        }
        $type = $request->type;
        if($type === 'google') {
            UserGoogle::where('user_id', $user->id)->delete();
            return response()->json([
                'status' => 'success'
            ]);
        }
        if($type === 'github') {
            UserGitHub::where('user_id', $user->id)->delete();
            return response()->json([
                'status' => 'success'
            ]);
        }
        if($type === 'discord') {
            UserDiscord::where('user_id', $user->id)->delete();
            return response()->json([
                'status' => 'success'
            ]);
        }
        return response()->json(['status' => 'error', 'message' => 'Unable to know what type of Oauth use.'], 500);

    }
}