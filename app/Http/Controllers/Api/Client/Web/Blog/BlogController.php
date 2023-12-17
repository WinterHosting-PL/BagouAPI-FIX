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
            $blogs = $elements->paginate(10, ['*'], 'page', $request->page);
            return response()->json(['status' => 'success', 'data' => BlogResource::collection($blogs), 'totalPage' => $blogs->lastPage()], 200);
        }

        $blogs = $elements->paginate(10);
        return response()->json(['status' => 'success', 'data' => BlogResource::collection($blogs), 'totalPage' => $blogs->lastPage()], 200);


    }
    public function get(String $slug)
    {
        $blog = Blog::where('slug', $slug)->firstOrFail();

        return response()->json(['status' => 'success', 'data' => new BlogResource($blog)], 200);
    }

}