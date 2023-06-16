<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    protected $table = 'tickets';

    /**
     * Fields that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'priority',
        'status',
        'license',
        'user_id',
        'logs_url',
        'name'
    ];
    public function messages()
    {
        return $this->hasMany(TicketMessage::class);
    }
    public function attachement()
    {
        return $this->hasMany(Attachment::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function license()
    {
        return $this->belongsTo(License::class, 'license_transaction_id', 'transaction');
    }
}
