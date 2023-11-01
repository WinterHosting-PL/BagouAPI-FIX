<?php

namespace App\Http\Controllers\Api\Client\Web;

use App\Mail\ContactEmail;
use App\Mail\LoginEmail;
use App\Mail\TestMail;
use App\Notifications\TestNotification;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Config;
use Notification;
use Illuminate\Support\Facades\Mail;
class ClientController extends BaseController
{
    public function getcrsf() {
       
        return csrf_token();
    }
    public function sendContact(Request $request) {
        $firstname = $request->firstname;
        $lastname = $request->lastname;
        $email = $request->email;
        $phone = $request->phone;
        $messages = $request->message;
        $society = $request->society;
        if(!$society) {
            $society = 'None';
        }
        if(!$phone) {
            $phone = 'None';
        }
        if(!$email || !$messages) {
            return response()->json(['status' => 'error', 'message' => 'No message provided'], 401);
        };

        try {
            Mail::to('contact@bagou450.com')
                ->send(new ContactEmail($firstname,$lastname,$email,$phone,$messages,$society));
        } catch(\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Can\'t send the mail.'
            ], 500);
        }
        return response()->json(['status' => 'success', 'message' => 'Email sent successfully'], 200);

    }
}

