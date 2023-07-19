<?php

namespace App\Http\Controllers\Api\Client\Web\Blog;

use App\Http\Controllers\Api\Client\Web\Blog\BlogResource;
use App\Models\Blog;
use Illuminate\Http\Request;

class BlogController {
     public function index(Request $request)
    {
        $elements = Blog::with('user');

        if ($request->search) {
            $elements->where('title', 'LIKE', '%' . $request->search . '%')->orWhere('content', 'LIKE', '%' . $request->search . '%');
        }
        if ($request->category) {
            $elements->where('category_id', $request->category);
        }
        if ($request->page) {
            $blogs = $elements->simplePaginate(10, ['*'], 'page', $request->page);
            return response()->json(['status' => 'sucess', 'data' => BlogResource::collection($blogs)], 200);
        }

        $blogs = $elements->paginate(10);
        return response()->json(['status' => 'sucess', 'data' => BlogResource::collection($blogs)], 200);


    }
    public function get(Blog $blog)
    {
        return response()->json(['status' => 'sucess', 'data' => new BlogResource($blog)], 200);
    }

}