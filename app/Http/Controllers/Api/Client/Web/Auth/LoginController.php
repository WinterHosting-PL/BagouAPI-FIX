<?php

namespace App\Http\Controllers\Api\Client\Web\Auth;

use App\Models\UserDiscord;
use App\Models\UserGoogle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
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

class LoginController extends Controller
{
    public function register(Request $request): \Illuminate\Http\JsonResponse
    {
        if (User::where('email', '=', $request->email)->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => 'This email was already taked.'
            ], 500);
        }
        if (User::where('name', '=', $request->name)->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => 'This username was already taked.'
            ], 500);
        }
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'name' => ['required']
        ]);
        $user = User::create([
            'name' => $credentials['name'],
            'email' => $credentials['email'],
        ]);

        $rand_str = bin2hex(random_bytes(128));
        while (User::where('login_token', '=', $rand_str)->exists()) {
            $rand_str = bin2hex(random_bytes(128));
        }
        User::where('id', '=', $user->id)->update(['login_token' => $rand_str]);
        Mail::to($user->email)
            ->send(new LoginEmail($rand_str));
        return response()->json([
            'status' => 'success',
        ], 200);
    }

    /**
     * Handle an authentication attempt.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function login(LoginRequest $request): \Illuminate\Http\JsonResponse
    {
        $user = auth('sanctum')->user();
        if ($user) {
            return response()->json(['status' => 'error', 'message' => 'You are already logged!.'], 500);
        }
        if (!User::where('email', '=', $request->email)->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => 'This username was not found in our database.'
            ], 500);
        }

        $credentials = array(
            'email' => $request->email,
        );
        $rand_str = bin2hex(random_bytes(128));
        while (User::where('login_token', '=', $rand_str)->exists()) {
            $rand_str = bin2hex(random_bytes(128));
        }

        User::where('email', '=', $request->email)->update(['login_token' => $rand_str]);
        Mail::to($request->email)
            ->send(new LoginEmail($rand_str));
        return response()->json([
            'status' => 'success',
        ], 200);
    }

    /**
     * @param Request $request
     * @return void
     * Login the user trough token
     */
    public function TokenLogin(Request $request)
    {
        if ($request->token === '' || !$request->token) {
            return response()->json(['status' => 'error', 'message' => 'Can\'t login a user with a empty token'], 500);
        }
        $user = User::where('login_token', $request->token)->firstOrFail();
        $token = $user->createToken('Invalid token.');
        $user->login_token = null;
        $user->save();
        return response()->json([
            'status' => 'success',
            'data' => [
                'email' => $user->email,
                'name' => $user->name,
                'access_token' => $token->plainTextToken,
                'token_type' => 'Bearer'
            ]
        ]);
    }

    public function tokendata(Request $request)
    {
        if ($request->token === '' || !$request->token) {
            return response()->json(['status' => 'error', 'message' => 'Invalid token.'], 500);
        }
        $user = User::where('login_token', $request->token)->firstOrFail();
        return response()->json([
            'status' => 'success',
            'data' => [
                'name' => $user->name,
                'email' => $user->email
            ]
        ]);
    }

    /**
     * Log the user out of the application.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function logout(): \Illuminate\Http\JsonResponse
    {
        $user = auth('sanctum')->user();
        if ($user) {
            $user->tokens()->delete();
            return response()->json([
                'status' => 'success',
            ], 200);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'You are already logout!'
        ], 500);
    }

    public function isLogged(Request $request): \Illuminate\Http\JsonResponse
    {
        $user = auth('sanctum')->user();
        if ($user) {
            $discord_avatar = null;
            $discord_username = null;
            $discord_discriminator = null;
            if ($user->discord) {
                $discordUser = $user->discord;
                $discord_avatar = "https://cdn.discordapp.com/avatars/$discordUser->discord_id/$discordUser->avatar";
                $discord_username = $discordUser->username;
                $discord_discriminator = $discordUser->discriminator;
            }
            return response()->json([
                'status' => true, 'data' => ['email' => $user->email,
                    'name' => $user->name,
                    'role' => $user->role,
                    'verified' => $user->email_verified_at !== null ? true : false,
                    'discord_username' => $discord_username,
                    'discord_discriminator' => $discord_discriminator,
                    'discord_avatar' => $discord_avatar]
            ], 200);

        }
        return response()->json([
            'status' => false, 'data' => []
        ], 200);

    }


    public function oauthlogin(Request $request)
    {
        $user = auth('sanctum')->user();
        if ($user) {
            return response()->json(['status' => 'error', 'message' => 'You are already logged!.'], 500);
        }
        $response = '';
        $redirectUri = config('services.discord.redirect') . "?type=$request->type";
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
        } else if ($request->type === 'google') {
            $response = 'https://accounts.google.com/o/oauth2/auth' .
                '?client_id=' . config('services.google.client_id') .
                '&redirect_uri=' . $redirectUri .
                '&response_type=code' .
                '&scope=email%20profile';

        }

        return response()->json(['status' => 'success', 'data' => ['url' => "$response"]]);
    }

    public function oauthloginCallback(Request $request)
    {
        $user = auth('sanctum')->user();
        if ($user) {
            return response()->json(['status' => 'error', 'message' => 'You are already logged!.'], 500);
        }
        if ($request->type === 'discord') {
            $clientId = config('services.discord.client');;
            $clientSecret = config('services.discord.client_secret');
            $redirectUri = config('services.discord.oauth_redirection');
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
                    $userDiscord = UserDiscord::where('discord_id', $userInfo['id'])->firstOrFail();
                    $user = User::where('id', $userDiscord->user_id)->firstOrFail();
                    $rand_str = bin2hex(random_bytes(128));
                    while (User::where('login_token', '=', $rand_str)->exists()) {
                        $rand_str = bin2hex(random_bytes(128));
                    }
                    $user->login_token = $rand_str;
                    $user->save();
                    return response()->json([
                        'status' => 'success',
                        'data' => [
                            'email' => $user->email,
                            'name' => $user->name,
                            'avatar' => $userDiscord->avatar,
                            'access_token' => $rand_str,
                            'token_type' => 'Bearer',
                            'discord_id' => $userInfo['id']
                        ]
                    ]);
                }
            }
            return response()->json(['status' => 'error', 'message' => 'Unable to retrieve user information from Discord'], 500);

        }
        if ($request->type === 'google') {
            $redirectUri = config('services.discord.redirect') . "?type=google";
            $response = Http::post('https://oauth2.googleapis.com/token', [
                'code' => $request->token,
                'client_id' => config('services.google.client_id'),
                'client_secret' => config('services.google.client_secret'),
                'redirect_uri' => $redirectUri,
                'grant_type' => 'authorization_code',
            ])->object();
            if(isset($response->error) || !isset($response->access_token)) {
                return response()->json(['status' => 'error', 'message' => 'An unexcepted error happend.'], 500);
            }

            $accessToken = $response->access_token;
            $profile = Http::get('https://www.googleapis.com/oauth2/v2/userinfo', [
                'access_token' => $accessToken,
            ])->object();
            $userGoogle = UserGoogle::where('google_id', "$profile->id")->first();
            if ($userGoogle) {
                //Compte google déja liée.
                $user = User::where('id', $userGoogle->user_id)->first();
                if ($user) {
                    $rand_str = bin2hex(random_bytes(128));
                    while (User::where('login_token', '=', $rand_str)->exists()) {
                        $rand_str = bin2hex(random_bytes(128));
                    }
                    $user->login_token = $rand_str;
                    $user->save();
                    return response()->json([
                        'status' => 'success',
                        'data' => [
                            'email' => $user->email,
                            'name' => $user->name,
                            'avatar' => $profile->picture,
                            'access_token' => $rand_str,
                            'token_type' => 'Bearer',
                            'google_id' => $profile->id
                        ]
                    ]);
                }
                return response()->json(['status' => 'error', 'message' => 'An unexcepted error happend.'], 500);
            } else {
                $user = User::where('email', $profile->email)->first();
                if ($user) {
                    return response()->json(['status' => 'error', 'message' => 'Please link your Google account to your Bagou450 account before logging in with Google.'], 500);
                }
                $user = new User();
                $user->email = $profile->email;
                $user->firstname = $profile->given_name;
                $user->lastname = $profile->family_name;
                $user->name = $profile->given_name;
                $user->save();
                $newGoogleuser = new UserGoogle();
                $newGoogleuser->google_id = "$profile->id";
                $newGoogleuser->user_id = $user->id;
                $newGoogleuser->avatar = $profile->picture;
                $newGoogleuser->save();
                 $rand_str = bin2hex(random_bytes(128));
                    while (User::where('login_token', '=', $rand_str)->exists()) {
                        $rand_str = bin2hex(random_bytes(128));
                    }
                    $user->login_token = $rand_str;
                    $user->save();
                    return response()->json([
                        'status' => 'success',
                        'data' => [
                            'email' => $user->email,
                            'name' => $user->name,
                            'avatar' => $profile->picture,
                            'access_token' => $rand_str,
                            'token_type' => 'Bearer',
                            'google_id' => $profile->id,
                        ]
                    ]);
            }
        }

        return response()->json(['status' => 'error', 'message' => 'Unable to know what type of Oauth use.'], 500);

    }
}