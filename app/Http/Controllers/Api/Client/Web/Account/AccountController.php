<?php
 
namespace App\Http\Controllers\Api\Client\Web\Account;
 
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
use Illuminate\Support\Facades\Hash;
use Illuminate\Contracts\Hashing\Hasher;

class AccountController extends Controller
{
    public function edit(Request $request): \Illuminate\Http\JsonResponse {
        $user = auth('sanctum')->user();
        User::where('id', '=', $user->id)->update(['email' => $request->email]);
        $loginController = new \App\Http\Controllers\Api\Client\Web\Auth\LoginController();
        $loginController->logout();
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

}