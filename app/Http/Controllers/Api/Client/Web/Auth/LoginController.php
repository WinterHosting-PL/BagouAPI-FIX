<?php
 
namespace App\Http\Controllers\Api\Client\Web\Auth;
 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Routing\Controller;
use App\Models\User;
use App\Http\Requests\LoginRequest;

class LoginController extends Controller
{
    public function register(Request $request) {
        if($request->social) {
            $user = Socialite::driver($request->social)->user();
            $user = User::updateOrCreate([
                'name' => $user->name,
                'email' => $user->email,

            ]);
            Auth::login($user);
            return response()->json([
                'status' => 'success',
                'data' => [
                    'email' => Auth::email(),
                ]
            ]);
        }
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
            'name' => ['required']
        ]);

        $user = User::create($credentials);

        Auth::login($user);
        $user =  User::where('id', '=', Auth::id())->firstOrFail(); 
        return response()->json([
            'status' => 'success',
            'data' => [
                'email' => $user->email,
                'name' => $user->name
            ]
        ]);
    }
    /**
     * Handle an authentication attempt.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function login(LoginRequest $request)
    {
        
        if($request->social) {
            $User = Socialite::driver($request->social)->user();
            Auth::login($user);
        }
        $credentials = array(
            'email' => $request->email,
            'password' => $request->password
        );
        if (Auth::attempt($credentials, $request->remember)) {
            $request->session()->regenerate();
            $user =  User::where('id', '=', Auth::id())->firstOrFail();
            return response()->json([
                'status' => 'success',
                'data' => [
                    'email' => $user->email,
                    'name' => $user->name
                ]
            ]);
        }
        return response()->json([
            'status' => 'error',
            'message' => 'The provided credentials do not match our records.'
        ]);
    }
    /**
 * Log the user out of the application.
 *
 * @param  \Illuminate\Http\Request  $request
 * @return \Illuminate\Http\Response
 */
public function logout(Request $request)
{
    Auth::logout();
 
    $request->session()->invalidate();
 
    $request->session()->regenerateToken();
 
    return redirect('/');
}
}