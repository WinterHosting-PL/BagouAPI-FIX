<?php

namespace App\Http\Controllers\Api\Client\Web\Blog;

use Illuminate\Http\Resources\Json\JsonResource;

class BlogResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'author' => $this->user->firstname . ' ' . $this->user->lastname,
            'title' => $this->title,
            'category_id' => $this->category_id,
            'views' => $this->views,
            'slug' => $this->slug,
            'pictures' => $this->pictures,
            'content' => $this->content,
            'tags' => $this->tags
        ];
    }
}

