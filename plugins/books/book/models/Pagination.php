<?php

namespace Books\Book\Models;

use Model;
use October\Rain\Database\Builder;
use October\Rain\Database\Relations\HasMany;
use October\Rain\Database\Relations\HasOne;
use October\Rain\Database\Traits\Validation;
use RainLab\User\Models\User;

/**
 * Pagination Model
 *
 * @method HasOne chapter
 * @property  ?Chapter chapter
 *
 * @property ?Pagination next
 * @property ?Pagination prev
 *
 * @method HasOne next
 * @method HasOne prev
 * @method HasMany trackers
 */
class Pagination extends Model
{
    use Validation;

    /**
     * @var string table name
     */
    public $table = 'books_book_pagination';

    public const RECOMMEND_MAX_LENGTH = 7500;

    protected $fillable = [
        'page',
        'length',
        'content',
        'chapter_id',
        'next_id',
        'prev_id',
    ];

    /**
     * @var array rules for validation
     */
    public $rules = [
        'page' => 'required|integer',
        'length' => 'required|integer',
        'content' => 'required|string',
        'chapter_id' => 'required|integer|exists:books_book_chapters,id',
        'next_id' => 'nullable|integer|exists:books_book_pagination,id',
        'prev_id' => 'nullable|integer|exists:books_book_pagination,id',
    ];


    public $belongsTo = [
        'chapter' => [Chapter::class,'key' => 'chapter_id','otherKey' => 'id'],
        'next' => [Pagination::class, 'key' => 'next_id', 'otherKey' => 'id'],
        'prev' => [Pagination::class, 'key' => 'prev_id', 'otherKey' => 'id'],
    ];

    public function scopePage(Builder $builder, int $page): Builder
    {
        return $builder->where('page', '=', $page);
    }

    public function setNeighbours()
    {
        $this->setPrev();
        $this->setNext();
    }

    public function setNext(){
        $this->update(['next_id' => $this->chapter->pagination()->page($this->page + 1)?->first()?->id]);
    }

    public function setPrev(){
        $this->update(['prev_id' => $this->chapter->pagination()->page($this->page - 1)?->first()?->id]);
    }
}
