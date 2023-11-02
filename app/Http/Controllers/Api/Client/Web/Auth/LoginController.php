<?php

namespace App\Http\Controllers\Api\Client\Web\Auth;

use App\Mail\ContactEmail;
use App\Mail\InvoiceMail;
use App\Mail\OrderConfirmed;
use App\Mail\PassKeyEmail;
use App\Mail\ProductDownloadEmail;
use App\Mail\ProductUpdateMail;
use App\Mail\TicketCreatedMail;
use App\Mail\TicketMessageAddedMail;
use App\Mail\TicketStatusUpdatedMail;
use App\Mail\WelcomeEmail;
use App\Models\Discount;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\UserDiscord;
use App\Models\UserGitHub;
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
use Rawilk\Webauthn\Models\WebauthnKey;

class LoginController extends Controller
{
    public function register(Request $request): \Illuminate\Http\JsonResponse
    {

        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'name' => ['required']
        ]);
        if (User::where('email', '=', $credentials['email'])->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => 'This email was already taked.'
            ], 500);
        }
        if (User::where('name', '=', $credentials['name'])->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => 'This username was already taked.'
            ], 500);
        }
        User::create([
            'name' => $credentials['name'],
            'email' => $credentials['email'],
        ]);

        try {
            Mail::to($credentials['email'])
                ->send(new WelcomeEmail());
        } catch(\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error during email sending.'
            ], 500);
        }

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
        $user = User::where('email', '=', $request->email)->orWhere('name', '=', $request->email)->Orwhere('email', '=', "$request->email@gmail.com")->first();
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'This username was not found in our database.'
            ], 500);
        }

            $rand_str = bin2hex(random_bytes(16));
            while (User::where('login_token', '=', $rand_str)->exists()) {
                $rand_str = bin2hex(random_bytes(16));
            }

            $user->update(['login_token' => $rand_str]);
            $user->save();
            try {
                Mail::to($request->email)
                    ->send(new LoginEmail($rand_str));
            } catch(\Exception $e) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Error during email sending.'
                ], 500);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'email'
            ], 200);


    }
    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * Check if a account exist
     */
   public function isAccount(Request $request) {
       if (!User::where('email', '=', $request->email)->orWhere('name', '=', $request->email)->Orwhere('email', '=', "$request->email@gmail.com")->exists()) {
           return response()->json([
               'status' => 'error',
               'message' => 'This username was not found in our database.'
           ], 500);
       }
       return response()->json([
           'status' => 'success',
           'message' => 'User found!'
       ]);
   }
    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
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
        $discount = Discount::first();
        if($discount) {
            $discount = [
                'status' => true,
                'code' => $discount->code,
                'value' => $discount->value
            ];
        } else {
            $discount = ['status' => false];
        }

        if ($user) {
            if (!$request->infos) {
                return response()->json([
                    'status' => true, 'data' => ['email' => $user->email,
                        'name' => $user->name,
                        'role' => $user->role]
                ], 200);
            }

            $discordUser = array('status' => !!$user->discord);
            if ($user->discord) {
                $aa = $user->discord;
                $discord_avatar = "https://cdn.discordapp.com/avatars/$aa->discord_id/$aa->avatar";
                $discord_username = $aa->username;
                $discord_discriminator = $aa->discriminator;
                $discordUser['data'] = array(
                    'avatar' => $discord_avatar,
                    'username' => $discord_username,
                    'discriminator' => $discord_discriminator
                );
            }
            $googleUser = array('status' => !!$user->google);
            if ($user->google) {
                $googleUser['data'] = array(
                    'avatar' => $user->google->avatar,
                    'username' => $user->google->username
                );
            }
            $githubUser = array('status' => !!$user->github);
            if ($user->github) {
                $githubUser['data'] = array(
                    'avatar' => $user->github->avatar,
                    'username' => $user->github->username,
                    'plan' => $user->github->plan
                );
            }
            return response()->json([
                'status' => true, 'data' => ['email' => $user->email,
                    'name' => $user->name,
                    'role' => $user->role,
                    'verified' => true,
                    'discord' => $discordUser,
                    'google' => $googleUser,
                    'github' => $githubUser
                ], 'discount' => $discount], 200);

        }
        return response()->json([
            'status' => false, 'data' => [], 'discount' => $discount
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

        } else if ($request->type === 'github') {
            $response = 'https://github.com/login/oauth/authorize' .
                '?client_id=' . config('services.github.client_id') .
                '&redirect_uri=' . $redirectUri .
                '&scope=read:user%20user:email';
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
            $redirectUri = config('services.discord.redirect') . "?type=$request->type";
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
                        $user = User::where('id', $userDiscord->user_id)->first();
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
                                    'avatar' => $userDiscord->avatar,
                                    'access_token' => $rand_str,
                                    'token_type' => 'Bearer',
                                    'discord_id' => $userDiscord->discord_id
                                ]
                            ]);
                        }
                        return response()->json(['status' => 'error', 'message' => 'An unexcepted error happend.'], 500);
                    } else {
                        $user = User::where('email', $userInfo['email'])->first();
                        if ($user) {
                            return response()->json(['status' => 'error', 'message' => 'Please link your Discord account to your Bagou450 account before logging in with Discord.'], 500);
                        }
                        $user = new User();
                        $user->email = $userInfo['email'];
                        $user->name = $userInfo['username'];
                        $user->save();
                        $discriminator =  $userInfo['discriminator'];
                        $newDiscorduser = new UserDiscord();
                        $newDiscorduser->discord_id = "$discordId";
                        $newDiscorduser->username = $userInfo['username'];
                        $newDiscorduser->email = $userInfo['email'];
                        $newDiscorduser->discriminator = "$discriminator";
                        $newDiscorduser->user_id = $user->id;
                        $newDiscorduser->avatar = 'https://cdn.discordapp.com/avatars/' . $discordId . '/' . $userInfo['avatar'] . '.png';
                        $newDiscorduser->save();
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
                                'avatar' => $newDiscorduser->avatar,
                                'access_token' => $rand_str,
                                'token_type' => 'Bearer',
                                'discord_id' => $newDiscorduser->discord_id,
                            ]
                        ]);
                    }
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
            if (isset($response->error) || !isset($response->access_token)) {
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
                $newGoogleuser->username = $profile->name;
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
        if ($request->type === 'github') {
            $redirectUri = config('services.discord.redirect') . "?type=github";
            $response = Http::withHeaders(['Accept' => 'application/json'])->post('https://github.com/login/oauth/access_token', [
                'code' => $request->token,
                'client_id' => config('services.github.client_id'),
                'client_secret' => config('services.github.client_secret'),
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
                //Compte GitHub déja liée.
                $user = User::where('id', $userGithub->user_id)->first();
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
                            'avatar' => $profile->avatar_url,
                            'access_token' => $rand_str,
                            'token_type' => 'Bearer',
                            'github_id' => $profile->id
                        ]
                    ]);
                }
                return response()->json(['status' => 'error', 'message' => 'An unexcepted error happend.'], 500);
            }
            $githubEmail = Http::withHeaders([
                'Authorization' => "Bearer $response->access_token",
            ])->get('https://api.github.com/user/emails')->json();
            if (!isset($githubEmail[0]['email'])) {
                return response()->json(['status' => 'error', 'message' => 'An unexcepted error happend.'], 500);
            }
            $counter = 0;
            while (!$githubEmail[$counter]['primary']) {
                $counter++;
                if (!isset($githubEmail[$counter])) {
                    return response()->json(['status' => 'error', 'message' => 'Can\'t find any primary email adress on this Github account.'], 500);
                }
            }
            $githubEmail = !$githubEmail[$counter]['email'];
            if (User::where('email', $githubEmail)->exists()) {
                return response()->json(['status' => 'error', 'message' => 'Please link your Github account to your Bagou450 account before logging in with Github.'], 500);
            }
            $user = new User();
            $user->email = $githubEmail;
            $user->name = $profile->login;
            $user->save();
            $newGithubuser = new UserGitHub();
            $newGithubuser->github_id = "$profile->id";
            $newGithubuser->user_id = $user->id;
            $newGithubuser->username = $profile->login;
            $newGithubuser->avatar = $profile->avatar_url;
            $newGithubuser->plan = $profile->plan->name;
            $newGithubuser->save();
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
                    'avatar' => $profile->avatar_url,
                    'access_token' => $rand_str,
                    'token_type' => 'Bearer', 'google_id' => $profile->id,
                ]]);
        }

        return response()->json(['status' => 'error', 'message' => 'Unable to know what type of Oauth use.'], 500);

    }

}