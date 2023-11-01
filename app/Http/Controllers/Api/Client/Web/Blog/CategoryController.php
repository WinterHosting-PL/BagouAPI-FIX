<?php

namespace App\Http\Controllers\Api\Client\Web\Blog;

use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController{
    public function index(Request $request) {

        return response()->json(['status' => 'success', 'data' => Category::get()], 200);
    }

    public function get(Category $category) {
        return response()->json(['status' => 'success', 'data' => $category], 200);

    }

}