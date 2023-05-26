<?php
 
namespace App\Http\Controllers\Api\Client\Web\Dashboard;
 
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;


class DashboardController extends Controller
{
    public function dashboard(Request $request) {
        return 'YES';
    }
   
}