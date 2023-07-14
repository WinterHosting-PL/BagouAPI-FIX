<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserGitHub extends Model
{
    use HasFactory;

    protected $table = 'users_github';

    protected $fillable = [
        'user_id',
        'github_id',
        'avatar',
        'username',
        'plan'
    ];

    public $timestamps = true;

    // Relation avec le modèle User
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
