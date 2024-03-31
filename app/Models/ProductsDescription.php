<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductsDescription extends Model
{
    use HasFactory;
    protected $fillable = [
        'product_id',
        'tag',
        'description',
        'language'
    ];
    protected function product(): BelongsTo
    {
        return $this->belongsTo(Products::class, 'product_id');
    }
}
