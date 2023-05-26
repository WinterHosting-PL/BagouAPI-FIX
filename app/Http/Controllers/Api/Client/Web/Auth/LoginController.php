<?php

namespace App\Http\Controllers\Api\Client\Web\Auth;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
        if($request->token === '' || !$request->token) {
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
    public function tokendata(Request $request) {
        if($request->token === '' || !$request->token) {
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

    public function sendverificationemail(Request $request): \Illuminate\Http\JsonResponse
    {
        if (Auth::check()) {
            $user = Auth::user();
            if ($user->email_verified_at) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'This email was already verified in pastpriv.'
                ], 500);
            }
            $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
            $randomString = '';

            for ($i = 0; $i < 32; $i++) {
                $index = rand(0, strlen($characters) - 1);
                $randomString .= $characters[$index];
            }
            User::where('id', '=', $user->id)->update(['email_token' => $randomString]);
            Mail::to($user->email)
                ->send(new LoginEmail($randomString));
            return response()->json([
                'status' => 'success', 'message' => 'Please check your mailbox.'
            ], 200);
        }
        return response()->json([
            'status' => 'error', 'message' => 'Your are not logged.'
        ], 500);
    }

    public function isLogged(Request $request): \Illuminate\Http\JsonResponse
    {
        $user = auth('sanctum')->user();
        if ($user) {
            return response()->json([
                'status' => true, 'data' => ['email' => $user->email, 'name' => $user->name, 'verified' => $user->email_verified_at !== null ? true : false]
            ], 200);

        }
        return response()->json([
            'status' => false, 'data' => []
        ], 200);

    }

    public function verifyemail(Request $request)
    {
        $token = $request->token;
        $user = User::where('email_token', '=', $token)->firstOrFail();
        User::where('id', '=', $user->id)->update(['email_verified_at' => \Carbon\Carbon::now(), 'email_token' => 'null']);
        return Redirect::to('http://bagou450.com');
    }


}