<?php

namespace App\Http\Controllers\Api\Client\Web;

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
    
}

