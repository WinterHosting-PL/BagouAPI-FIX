<?php

namespace App\Http\Controllers\Api\Client\Web\Admin\Blog;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AdminCategoryController
{
    public function create(Request $request) {
        $user = auth('sanctum')->user();

        if (!$user || $user->role !== 1) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }
             $validator = Validator::make($request->all(), [
                    'name' => 'required|strin
                     g',
                    'slug' => 'required|string'
             ]);
                      if ($validator->fails()) {
                    return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
                }
            $category = new Category;
            $category->name = $request->name;
            $category->slug = $request->slug;

            $category->save();

            return response()->json(['status' => 'success', 'message' => 'Category created successfully'], 201);

    }
    public function remove(Category $category) {
         $user = auth('sanctum')->user();

        if (!$user || $user->role !== 1) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }
        $category->delete();
        return response()->json(['status' => 'success', 'message' => 'Category removed successfully'], 201);
    }
    public function edit(Request $request, Category $category)
{
    $user = auth('sanctum')->user();

    if (!$user || $user->role !== 1) {
        return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
    }

    $validator = Validator::make($request->all(), [
        'name' => 'required|string',
        'slug' => 'required|string'
 ]);

    if ($validator->fails()) {
        return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
    }


    $category->name = $request->name;
    $category->slug = $request->slug;

    $category->save();

    return response()->json(['status' => 'success', 'message' => 'Category updated successfully'], 200);
}
}