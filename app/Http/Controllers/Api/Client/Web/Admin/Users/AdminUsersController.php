<?php

namespace App\Http\Controllers\Api\Client\Web\Admin\Users;

use App\Models\Mailinglist;
use App\Models\User;
use Illuminate\Http\Request;
use Infomaniak\ClientApiNewsletter\Action;
use Infomaniak\ClientApiNewsletter\Client;

class AdminUsersController
{
  public function get(Request $request) {
      $user = auth('sanctum')->user();

      if (!$user || $user->role !== 1) {
          return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
      }
  $perPage = $request->perpage ?? 15;
    $page = $request->page ?? 1;
    $search = $request->search ?? '';

    $query = User::with(['discord', 'github', 'google'])
        ->where(function ($query) use ($search) {
            if ($search) {
                $query->where('name', 'LIKE', '%' . $search . '%')
                    ->orWhere('email', 'LIKE', '%' . $search . '%')
                    ->orWhereHas('discord', function ($query) use ($search) {
                        $query->where('username', 'LIKE', '%' . $search . '%');
                    })
                    ->orWhereHas('github', function ($query) use ($search) {
                        $query->where('username', 'LIKE', '%' . $search . '%');
                    })
                    ->orWhereHas('google', function ($query) use ($search) {
                        $query->where('username', 'LIKE', '%' . $search . '%');
                    });
            }
        });

    $total = ceil($query->count()/$perPage);

    $users = $query->skip(($page - 1) * $perPage)
        ->take($perPage)
        ->get();

    return response()->json(['status' => 'success', 'data' => $users, 'total' => $total]);
  }
  public function edit(Int $selectedUser, Request $request) {
      $user = auth('sanctum')->user();

      if (!$user || $user->role !== 1) {
          return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
      }
      $validated = $request->validate([
          'name' => 'required',
          'email' => 'required',
          'address' => 'required',
          'city' => 'required',
          'country' => 'required',
          'region' => 'required',
          'postal_code' => 'required',
          'firstname' => 'required',
          'lastname' => 'required',
          'role' => 'required',
      ]);

      $user = User::findOrFail($selectedUser);
      $user->update($request->only([
          'name',
          'email',
          'society',
          'address',
          'city',
          'country',
          'region',
          'postal_code',
          'phone_number',
          'firstname',
          'lastname',
          'role',
      ]));
    return response()->json(['status' => 'success', 'message' => 'User updated successfully.']);

  }
  public function syncInfomaniak() {
      $user = auth('sanctum')->user();

      if (!$user || $user->role !== 1) {
          return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
      }
      $users = User::all();
      $contact = [];
      foreach($users as $user) {
          $contact[] = ['email' => $user->email];
      }
      $client = new Client(config('services.infomaniak.api') , config('services.infomaniak.secret'));
      $client->post(Client::MAILINGLIST , [
          'id' => Mailinglist::first()->infomaniak_id ,
          'action' => Action::IMPORTCONTACT ,
          'params' => [
              'contacts' => $contact
          ]
      ]);
      return response()->json(['status' => 'success', 'message' => 'User updated successfully.']);
  }

}