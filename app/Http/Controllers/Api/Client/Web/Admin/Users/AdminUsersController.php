<?php

namespace App\Http\Controllers\Api\Client\Web\Admin\Users;

use App\Models\User;
use Illuminate\Http\Request;

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

    $total = $query->count();

    $users = $query->skip(($page - 1) * $perPage)
        ->take($perPage)
        ->get();

    return response()->json(['status' => 'success', 'data' => $users, 'total' => $total]);
  }
  public function edit(User $selectedUser, Request $request) {
      $user = auth('sanctum')->user();

      if (!$user || $user->role !== 1) {
          return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
      }
      $validated = $request->validate([
          'name' => 'required',
          'email' => 'required',
          'society' => 'optional',
          'address' => 'required',
          'city' => 'required',
          'country' => 'required',
          'region' => 'required',
          'postal_code' => 'required',
          'phone_number' => 'required',
          'firstname' => 'required',
          'lastname' => 'required',
          'role' => 'required',
          'userid' => 'required'
      ]);
      $selectedUser->update([
          'name' => $request->name,
          'email' => $request->email,
          'society' => $request->society,
          'address' => $request->address,
          'city' => $request->city,
          'country' => $request->country,
          'region' => $request->region,
          'postal_code' => $request->postal_code,
          'phone_number' => $request->phone_number,
          'firstname' => $request->firstname,
          'lastname' => $request->lastname,
          'role' => $request->role,
      ]);
    return response()->json(['status' => 'success', 'message' => 'User updated successfully.']);

  }
}