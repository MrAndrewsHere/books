<?php namespace Books\Reviews\Models;

use Model;
use Mtvs\Reviews\ReviewConcerns;
use October\Rain\Database\Traits\Validation;

/**
 * Reviews Model
 */
class Review extends Model
{
    use Validation;
    use ReviewConcerns;


    /**
     * @var string table associated with the model
     */
    public $table = 'books_reviews_reviews';

    /**
     * @var array guarded attributes aren't mass assignable
     */
    protected $guarded = ['*'];

    /**
     * @var array fillable attributes are mass assignable
     */
    protected $fillable = ['rating', 'title', 'body'];

    /**
     * @var array rules for validation
     */
    public $rules = [
        'rating' => ['required', 'numeric', 'min:1', 'max:5'],
        'title' => ['required', 'string', 'max:255'],
        'body' => ['required', 'string'],];

    /**
     * @var array Attributes to be cast to native types
     */
    protected $casts = [];

    /**
     * @var array jsonable attribute names that are json encoded and decoded from the database
     */
    protected $jsonable = [];

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
        'updated_at',
        'approval_at'
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


    /**
     * @var int
     */
    protected $perPage = 9;

    /**
     * Get the columns which require approval when they are updated
     *
     * @return array
     **/
    public function approvalRequired()
    {
        return [
            'title', 'body',
        ];
    }
}
