<?php namespace Books\Profile\Models;

use Books\User\Classes\PrivacySettingsEnum;
use Books\User\Classes\SettingsRelationCast;
use Model;
use October\Rain\Database\Builder;
use System\Models\File;
use RainLab\User\Models\User;
use October\Rain\Database\Traits\Validation;


/**
 * Profile Model
 */
class Profile extends Model
{
    use Validation;

    /**
     * @var string table associated with the model
     */
    public $table = 'books_profile_profiles';

    /**
     * @var array guarded attributes aren't mass assignable
     */
    protected $guarded = ['*'];

    public array $endingArray = ['Автор','Автора','Авторов'];


    /**
     * @var array fillable attributes are mass assignable
     */
    protected $fillable = [
        'username',
        'username_clipboard',
        'username_clipboard_comment',
        'status',
        'about',
        'avatar',
        'banner',
        'ok',
        'phone',
        'tg',
        'vk',
        'email',
        'website',
    ];

    /**
     * @var array Attributes to be cast to native types
     */
    protected $casts = [];

    /**
     * @var array rules for validation
     */
    public $rules = [
        'username' => 'required|between:2,255|unique:books_profile_profiles',
        'username_clipboard' => 'nullable|between:2,255|unique:books_profile_profiles',
        'username_clipboard_comment' => 'nullable|string',
        'avatar' => 'nullable|image|mimes:jpg,png|dimensions:min_width=168,min_height=168',
        'banner' => 'nullable|image|mimes:jpg,png|dimensions:min_width=1152,min_height=160',
        'status' => 'nullable|string',
        'about' => 'nullable|string',
        'website' => 'nullable|url',
        'email' => 'nullable|email',
        'phone' => 'nullable|string',
        'tg' => 'nullable|string',
        'ok' => 'nullable|url',
        'vk' => 'nullable|url',
    ];
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
        'updated_at'
    ];

    /**
     * @var array hasOne and other relations
     */
    public $hasOne = [];

    public $belongsTo = ['user' => User::class, 'key' => 'id', 'otherKey' => 'user_id'];
    public $belongsToMany = [];
    public $morphTo = [];
    public $morphOne = [];
    public $morphMany = [];
    public $attachOne = [
        'banner' => [File::class],
        'avatar' => [File::class],
    ];
    public $attachMany = [];

    public function getIsCurrentAttribute(): bool
    {
        return !!$this->user->current_profile_id == $this->id;
    }

    public function getFirstLatterAttribute(){
        return strtoupper($this->username ? mb_substr($this->username,0,1) : 'A');
    }

    public function scopeSearchByString(Builder $query, string $string){
        return $query->where('username', 'like', "%$string%");
    }

    public function publicBooks(){
        return $this->user->books()->public();
    }
}
