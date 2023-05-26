<?php

namespace App\Models;

class TicketMessage
{
    protected $table = 'ticket_messages';

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}