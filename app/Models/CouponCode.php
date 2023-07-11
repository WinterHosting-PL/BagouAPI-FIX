<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CouponCode extends Model
{
    use HasFactory;

    protected $table = 'COUPON_CODES';
    protected $primaryKey = 'ID';
    protected $fillable = [
        'Valeur',
        'Nom'
    ];
    protected $casts = [
        'Valeur' => 'float',
    ];
}