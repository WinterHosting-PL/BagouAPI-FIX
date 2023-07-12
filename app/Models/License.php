<?php

namespace App\Models;

use Illuminate\Container\Container;
use Znck\Eloquent\Traits\BelongsToThrough;
use Pterodactyl\Contracts\Extensions\HashidsInterface;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $schedule_id
 * @property int $sequence_id
 * @property string $action
 * @property string $payload
 * @property int $time_offset
 * @property bool $is_queued
 * @property bool $continue_on_failure
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property string $hashid
 */
class License extends Model
{
    /**
     * The resource name for this model when it is transformed into an
     * API representation using fractal.
     */
    public const RESOURCE_NAME = 'licenses';


    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'licenses';


    /**
     * Fields that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'blacklisted',
        'buyer',
        'fullname',
        'ip',
        'maxusage',
        'name',
        'transaction',
        'usage',
        'buyerid',
        'sxcid',
        'bbb_id',
        'bbb_license',
        'user_id',
        'order_id'
    ];

    /**
     * Cast values to correct type.
     *
     * @var array
     */
    protected $casts = [
        'blacklisted' => 'boolean',
        'buyer' => 'string',
        'name' => 'string',
        'ip' => 'json',
        'maxusage' => 'integer',
        'transaction',
        'usage' => 'integer',
        'buyerid' => 'string',
        'sxcid' => 'string',
        'bbb_id' => "string",
        'bbb_license' => 'string',
        'user_id' => 'integer',
        'order_id' => 'integer'
    ];
}