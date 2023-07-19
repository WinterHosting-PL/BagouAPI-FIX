<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class License extends Model
{
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
        'product_id',
        'ip',
        'maxusage',
        'usage',
        'license',
        'version',
        'user_id',
        'order_id',
    ];

    /**
     * Cast values to correct type.
     *
     * @var array
     */
    protected $casts = [
        'blacklisted' => 'boolean',
        'product_id' => 'integer',
        'ip' => 'json',
        'maxusage' => 'integer',
        'usage' => 'integer',
        'license' => 'string',
        'version' => 'decimal:8,2',
        'user_id' => 'integer',
        'order_id' => 'integer',
    ];

    /**
     * Get the associated product.
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the associated user.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the associated order.
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
