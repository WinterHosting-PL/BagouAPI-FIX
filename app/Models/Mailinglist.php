<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mailinglist extends Model
{
    use HasFactory;
    protected $table = 'mailinglists'; // replace with your table name

    protected $fillable = [
        'infomaniak_id',
        'name',
    ];

}
