<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class TicketMessage extends Model
{
    protected $table = 'ticket_messages';
    /**
     * Fields that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'ticket_id',
        'user_id',
        'discord_id',
        'discord_user_id',
        'position',
        'content'
    ];
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}