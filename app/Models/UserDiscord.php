<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserDiscord extends Model
{
    use HasFactory;

    protected $table = 'users_discord';

    protected $fillable = [
        'user_id',
        'username',
        'avatar',
        'discriminator',
        'email',
        'discord_id'
    ];

    public $timestamps = false;

    // Relation avec le modèle User
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
