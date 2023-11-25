<?php

namespace App\Models;

use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
class Products extends Model
{
    /**
     * The resource name for this model when it is transformed into an
     * API representation using fractal.
     */
    public const RESOURCE_NAME = 'products';


    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'products';


    /**
     * Fields that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'name',
        'tab',
        'tabroute',
        'new',
        'version',
        'sxcname',
        'licensed',
        'bbb_id',
        'tag',
        'description',
        'link',
        'price',
        'icon',
        'hide',
        'extension',
        'stripe_id',
        'stripe_price_id',
        'extension_product',
        'slug',
        'category'
    ];
    /**
     * Cast values to correct type.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer:unique',
        'name' => 'string',
        'tab' => 'boolean',
        'tabroute' => 'string',
        'new' => 'boolean',
        'version' => 'float',
        "sxcname" => 'string',
        'licensed' => 'boolean',
        'bbb_id' => 'integer',
        'tag' => 'string',
        'description' => 'string',
        'link' => 'json',
        'icon' => 'string',
        'hide' => 'boolean',
        'price' => 'float',
        'extension' => 'boolean',
        'stripe_id' => 'string',
        'stripe_price_id' => 'string',
        'extension_product' => 'integer',
        'slug' => 'string',
        'category' => 'string'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    public function extension_product(): BelongsTo
    {
        return $this->belongsTo(Products::class, 'extension_product');
    }
}