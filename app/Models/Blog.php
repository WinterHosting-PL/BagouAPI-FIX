<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Blog extends Model
{
    protected $table = 'blog';

    protected $fillable = [
        'user_id', 'title', 'category_id', 'views', 'slug', 'pictures', 'content'
    ];

    /**
     * Cast values to correct type.
     *
     * @var array
     */
    protected $casts = [
        'user_id' => 'integer',
        'slug' => 'string',
                'pictures' => 'array',
        'views' => 'integer',
        'category_id' => 'integer',
        'title' => 'string',
        'content' => 'string',

    ];
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'blog_tags');
    }
}
