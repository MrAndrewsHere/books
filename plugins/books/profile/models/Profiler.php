<?php namespace Books\Profile\Models;

use Model;

/**
 * Profiler Model
 */
class Profiler extends Model
{
    use \October\Rain\Database\Traits\Validation;

    public $timestamps = false;

    /**
     * @var string table associated with the model
     */
    public $table = 'books_profile_profilers';

    /**
     * @var array guarded attributes aren't mass assignable
     */
    protected $guarded = ['*'];

    /**
     * @var array fillable attributes are mass assignable
     */
    protected $fillable = ['profile_id', 'entity_type', 'ids'];

    /**
     * @var array rules for validation
     */
    public $rules = [];

    /**
     * @var array Attributes to be cast to native types
     */
    protected $casts = [];

    /**
     * @var array jsonable attribute names that are json encoded and decoded from the database
     */
    protected $jsonable = [
        'ids'
    ];

    /**
     * @var array appends attributes to the API representation of the model (ex. toArray())
     */
    protected $appends = [];

    /**
     * @var array hidden attributes removed from the API representation of the model (ex. toArray())
     */
    protected $hidden = [];

    /**
     * @var array dates attributes that should be mutated to dates
     */
    protected $dates = [
        'created_at',
        'updated_at'
    ];

    /**
     * @var array hasOne and other relations
     */
    public $hasOne = [];
    public $hasMany = [];
    public $belongsTo = [];
    public $belongsToMany = [];
    public $morphTo = [];
    public $morphOne = [];
    public $morphMany = [];
    public $attachOne = [];
    public $attachMany = [];

    public function getIds()
    {
        return $this->ids;
    }

    public function addEntity(Model $model)
    {
        $this->update(['ids' => $this->getIds() + [$model->id]]);
    }
}
