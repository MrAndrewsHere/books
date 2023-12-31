<?php

namespace Books\Orders\Models;

use App\traits\HasUserScope;
use Books\Orders\Classes\Enums\OrderStatusEnum;
use Illuminate\Support\Str;
use Model;
use October\Rain\Database\Builder;
use October\Rain\Database\Relations\BelongsTo;
use October\Rain\Database\Relations\HasMany;
use October\Rain\Database\Traits\Validation;
use RainLab\User\Models\User;

/**
 * Order Model
 *
 * @method HasMany products
 * @method HasMany promocodes
 * @method HasMany editions
 * @method HasMany awards
 * @method HasMany donations
 * @method HasMany deposites
 * @method BelongsTo user
 *
 * @link https://docs.octobercms.com/3.x/extend/system/models.html
 */
class Order extends Model
{
    use Validation;
    use HasUserScope;

    /**
     * @var string table name
     */
    public $table = 'books_orders_orders';

    public $rules = [
        'user_id' => 'required|integer',
        'status' => 'integer',
        'amount' => 'sometimes|integer|min:0',
    ];

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
     * @var array Attributes to be cast to native types
     */
    protected $casts = [
        'type' => OrderStatusEnum::class,
    ];

    protected function beforeCreate()
    {
        $this->status = OrderStatusEnum::CREATED->value;
    }

    /**
     * @var array
     */
    public $belongsTo = [
        'user' => [
            User::class,
        ],
    ];

    /**
     * @var array hasMany relationship
     */
    public $hasMany = [
        'products' => [OrderProduct::class],
        'awards' => [
            OrderProduct::class,
            'scope' => 'awards',
        ],
        'donations' => [
            OrderProduct::class,
            'scope' => 'donations',
        ],
        'deposits' => [
            OrderProduct::class,
            'scope' => 'deposits',
        ],
        'editions' => [
            OrderProduct::class,
            'scope' => 'editions',
        ],
        'promocodes' => [OrderPromocode::class],
    ];

    /**
     * @param $query
     * @param OrderStatusEnum $status
     * @return void
     */
    public function scopeWhereStatus($query, OrderStatusEnum $status): void
    {
        $query->where('status', $status->value);
    }

    public function scopeCreated(Builder $builder)
    {
        return $builder->whereStatus(OrderStatusEnum::CREATED);
    }
}
