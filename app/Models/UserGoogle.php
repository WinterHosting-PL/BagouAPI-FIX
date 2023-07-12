<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserGoogle extends Model
{
    use HasFactory;

    protected $table = 'users_google';

    protected $fillable = [
        'user_id',
        'google_id',
        'avatar'
    ];

    public $timestamps = true;

    // Relation avec le modèle User
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
