<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Orders extends Model
{
    /**
     * The resource name for this model when it is transformed into an
     * API representation using fractal.
     */
    public const RESOURCE_NAME = 'orders';

    public $timestamps = true;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'orders';


    /**
     * Fields that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'products',
        'stripe_id',
        'status',
        'price',
        'token',
        'checkout',
        'address',
        'country',
        'city',
        'region',
        'postal_code',
        'name',
        'license'
        ];

    /**
     * Cast values to correct type.
     *
     * @var array
     */
    protected $casts = [
        'user_id' => 'integer',
        'products' => 'array',
        'stripe_id' => 'string',
        'status' => 'string',
        'price' => 'float',
        'token' => 'string',
        'checkout' => 'string',
        'address' => 'string',
        'country' => 'string',
        'city' => 'string',
        'region' => 'string',
        'postal_code' => 'string',
        'name' => 'string'
    ];
    public function user() {
        return $this->belongsTo(User::class, 'user_id');
    }
}