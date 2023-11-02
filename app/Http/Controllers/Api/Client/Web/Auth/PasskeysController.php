<?php

namespace App\Http\Controllers\Api\Client\Web\Auth;

use App\Http\Controllers\Controller;
use App\Mail\LoginEmail;
use App\Mail\PassKeyEmail;
use App\Models\User;
use Arr;
use DateInterval;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Rawilk\Webauthn\Actions\PrepareKeyCreationData;
use Rawilk\Webauthn\Facades\Webauthn;
use Rawilk\Webauthn\Models\WebauthnKey;


class PasskeysController extends Controller
{

    public function createOptions(Request $request)
    {
        $user = User::where('email', '=', $request->email)->orWhere('name', '=', $request->email)->Orwhere('email', '=', "$request->email@gmail.com")->first();
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'This username was not found in our database.'
            ], 500);
        }
        $requestOptions = null;
        $isKey = false;
        if(WebauthnKey::where('user_id', $user->id)->exists()) {
            try {
                $isKey = true;
                $requestOptions = \Rawilk\Webauthn\Facades\Webauthn::prepareAssertion($user);
            } catch(\Exception $e) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Error during assertion preparation.'
                ], 500);
            }

        } else {
            try {
                $requestOptions = app(PrepareKeyCreationData::class)($user);
            } catch(\Exception $e) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Error during creation preparation.'
                ], 500);
            }
        }

        return response()->json([
            'status' => 'success',
            'data' => $requestOptions,
            'isKey' => $isKey
        ], 200);
    }

    public function sendVerificationForAddPassKey(Request $request) {
        $credentials = $request->validate([
            'data' => ['required'],
            'email' => ['required', 'email']

        ]);
        $user = User::where('email', $credentials['email'])->firstOrFail();
        $passkey_token = substr(str_shuffle('0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 6);
        while(User::where('passkey_token', '=', $passkey_token)->exists()) {
            $passkey_token = substr(str_shuffle('0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 6);
        };
        $user->passkey_token = $passkey_token;
        $user->passkey_tmp = $credentials['data'];
        $user->save();
        try {
            Mail::to($user->email)
                ->send(new PassKeyEmail($passkey_token));
        } catch(\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error during email sending.'
            ], 500);
        }
        return response()->json([
            'status' => 'success'
        ], 200);
    }
    public function addPasskey(Request $request) {
        $credentials = $request->validate([
            'token' => ['required'],
        ]);

        $user = User::where('passkey_token', '=', $credentials['token'])->firstOrFail();
        $key = $user->passkey_tmp;

        try {
        app(\Rawilk\Webauthn\Actions\RegisterNewKeyAction::class)(
            $user,
            json_decode($key, true),
            $user->name,
        );
        } catch (\Rawilk\Webauthn\Exceptions\WebauthnRegisterException $e) {
            return response()->json([
                'status' => 'error',
                'message' => str($e->getMessage())
            ], 500);
        }
        $user->passkey_tmp = null;
        $user->passkey_token = null;
        $token = $user->createToken('Invalid token.');

        $user->save();
        return response()->json([
            'status' => 'success',
            'data' => $token->plainTextToken
        ], 200);
    }

    public function validateKey(Request $request) {
        $credentials = $request->validate([
            'data' => ['required'],
            'email' => ['required', 'email'],
        ]);
        $user = User::where('email', $credentials['email'])->firstOrFail();
        $valid = Webauthn::validateAssertion($user, $credentials['data']);
        if(!$valid) {
            return response()->json([
                'status' => 'error'
            ], 500);
        }
        $token = $user->createToken('Invalid token.');
        $user->save();

        return response()->json([
            'status' => 'success',
            'data' => $token->plainTextToken
        ], 200);
    }
}
