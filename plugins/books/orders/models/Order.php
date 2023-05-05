<?php

namespace Books\Orders\Models;

use Books\Orders\Classes\Enums\OrderStatusEnum;
use Model;
use RainLab\User\Models\User;

/**
 * Order Model
 *
 * @link https://docs.octobercms.com/3.x/extend/system/models.html
 */
class Order extends Model
{
    use \October\Rain\Database\Traits\Validation;

    /**
     * @var string table name
     */
    public $table = 'books_orders_orders';

    /**
     * @var array fillable attributes are mass assignable
     */
    protected $fillable = [
        'user_id',
        'status',
        'amount',
    ];

    /**
     * @var array dates attributes that should be mutated to dates
     */
    protected $dates = [
        'created_at',
        'updated_at',
    ];

    /**
     * @var string[]
     */
    protected $enums = [
        'status' => OrderStatusEnum::class,
    ];

    /**
     * @var array
     */
    public $belongsTo = [
        'user' => [
            User::class,
        ],
    ];

    /**
     * @var array
     */
    public $belongsToMany = [];

    /**
     * @var array
     */
    public $morphTo = [];

    /**
     * @param $query
     * @param OrderStatusEnum $status
     * @return void
     */
    public function scopeWhereStatus($query, OrderStatusEnum $status): void
    {
        $query->where('status', $status->getValue());
    }
}
