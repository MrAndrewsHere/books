<?php

namespace Books\Book\Models;

use Books\Book\Classes\EditionService;
use Books\Book\Classes\Enums\BookStatus;
use Books\Book\Classes\Enums\ChapterSalesType;
use Books\Book\Classes\Enums\EditionsEnums;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Db;
use Model;
use October\Rain\Database\Builder;
use October\Rain\Database\Relations\AttachOne;
use October\Rain\Database\Relations\BelongsTo;
use October\Rain\Database\Relations\HasMany;
use October\Rain\Database\Traits\Revisionable;
use October\Rain\Database\Traits\SoftDelete;
use October\Rain\Database\Traits\Validation;
use RainLab\User\Models\User;
use System\Models\File;
use System\Models\Revision;

/**
 * Edition Model
 *
 * * @method HasMany chapters
 * * @method AttachOne fb2
 * * @method BelongsTo book
 *
 * * @property  Book book
 * * @property  BookStatus status
 */
class Edition extends Model
{
    use Validation;
    use SoftDelete;
    use Revisionable;

    /**
     * @var string table name
     */
    public $table = 'books_book_editions';

    protected $revisionable = ['length', 'status'];

    public $revisionableLimit = 5000;

    public bool $forceRevision = false;

    public string $trackerChildRelation = 'chapters';

    protected $fillable = [
        'type',
        'download_allowed',
        'comment_allowed',
        'sales_free',
        'free_parts',
        'status',
        'price',
        'book_id',
        'length',
    ];

    protected $casts = [
        'type' => EditionsEnums::class,
        'free_parts' => 'integer',
        'price' => 'integer',
        'sales_free' => 'boolean',
        'download_allowed' => 'boolean',
        'comment_allowed' => 'boolean',
        'status' => BookStatus::class,
    ];

    protected $dates = ['sales_at'];

    /**
     * @var array rules for validation
     */
    public $rules = [
        'type' => 'required|integer',
        'price' => 'filled|integer|min:0|max:9999',
        'free_parts' => 'filled|integer',
        'download_allowed' => 'boolean',
        'comment_allowed' => 'boolean',
        'sales_free' => 'boolean',
        'fb2' => ['nullable', 'file', 'mimes:xml'],
    ];

    public $hasMany = [
        'chapters' => [Chapter::class, 'key' => 'edition_id', 'otherKey' => 'id'],
    ];

    public $attachOne = [
        'fb2' => File::class,
    ];

    public $belongsTo = [
        'book' => [Book::class, 'key' => 'book_id', 'otherKey' => 'id'],
    ];

    public $morphMany = [
        'revision_history' => [Revision::class, 'name' => 'revisionable'],
    ];

    public function service(): EditionService
    {
        return new EditionService($this);
    }

    public function getLastUpdatedAtAttribute()
    {
        return $this->revision_history()->where('field', '=', 'length')->orderByDesc('created_at')->first()?->created_at;
    }

    public function getUpdateHistoryAttribute()
    {
        $items = $this->revision_history()
            ->where('field', '=', 'length')
            ->get()
            ->chunkWhile(function ($value, $key, $chunk) {
                return ((int) $chunk->sum('new_value') - (int) $chunk->sum('old_value')) <= 5000;
            })->map(function ($collection) {
                return [
                    'date' => $collection->last()->created_at,
                    'value' => (int) $collection->last()->new_value - (int) $collection->first()->old_value,
                    'new_value' => (int) $collection->last()->new_value,
                ];
            })->filter(fn ($i) => (int) $i['value'] > 0)->reverse();

        $count = $items->count();
        $days = $count ? CarbonPeriod::create($items->last()['date'], $items->first()['date'])->count() : 0;

        $freq_string = $count ? getFreqString($count, $days) : '';
        $freq = $count ? $count / $days : 0;

        return [
            'freq' => $freq,
            'freq_string' => $freq_string,
            'items' => $items,
            'count' => $count,
            'days' => $days,
        ];
    }

    public function frozen()
    {
        $this->fill(['status' => BookStatus::FROZEN]);
        $this->save();
    }

    public function editAllowed(): bool
    {
        return ! $this->isPublished()
            || $this->getOriginal('sales_free')
            || in_array($this->getOriginal('status'), [BookStatus::WORKING, BookStatus::FROZEN])
            || ($this->getOriginal('status') === BookStatus::HIDDEN && ! $this->hadCompleted());
    }

    public function hadCompleted()
    {
        return $this->revision_history()->where(['field' => 'status', 'old_value' => BookStatus::COMPLETE->value])->exists();
    }

    public function isPublished(): bool
    {
        return (bool) $this->getOriginal('sales_at');
    }

    public function setPublishAt()
    {
        $this->attributes['sales_at'] = Carbon::now();
    }

    public function getAllowedStatusCases(): array
    {
        $cases = collect(BookStatus::publicCases());

        $cases = match ($this->getOriginal('status')) {
            BookStatus::WORKING => $this->hasSales() ? $cases->forget(BookStatus::HIDDEN) : $cases,// нельзя перевести в статус "Скрыто" если куплена хотя бы 1 раз
            BookStatus::COMPLETE => $cases->only(BookStatus::HIDDEN->value), // Из “Завершено” можем перевести только в статус “Скрыто”.
            BookStatus::FROZEN => collect(),
            BookStatus::HIDDEN => ! $this->isPublished() && $this->hadCompleted() ? collect() : $cases,//Если из статуса “Скрыто” однажды перевели книгу в статус “Завершено”, то книгу можно вернуть в статус “Скрыто” но редактирование и удаление глав будет невозможным.
            default => $cases
        };

        $cases[$this->getOriginal('status')->value] = $this->getOriginal('status');

        return $cases->toArray();
    }

    public function shouldDeferredUpdate(): bool
    {
        return $this->getOriginal('status') === BookStatus::COMPLETE;
    }

    public function hasSales()
    {
        return false;
    }

    public function shouldRevisionLength(): bool
    {
        return $this->isDirty('length') && ! $this->shouldDeferredUpdate() && in_array($this->getOriginal('status'), [BookStatus::WORKING, BookStatus::FROZEN]);
    }

    protected function beforeUpdate()
    {
        if (! $this->shouldRevisionLength()) {
            $this->revisionable = ['status'];
        }
    }

    protected function afterUpdate()
    {
        if ($this->wasChanged(['free_parts', 'status'])) {
            $this->setFreeParts();
        }
    }

    public function scopeWithProgress(Builder $builder, User $user): Builder
    {
        return $builder->withMax(['trackers as progress' => fn ($trackers) => $trackers->user($user)->withoutTodayScope()], 'progress');
    }

    public function getPriceAttribute()
    {
        return (bool) $this->sales_free ? 0 : $this->attributes['price'];
    }

    public function scopeMinPrice(Builder $builder, ?int $price): Builder
    {
        return $builder->where('price', '>=', $price);
    }

    public function scopeMaxPrice(Builder $builder, ?int $price): Builder
    {
        return $builder->where('price', '<=', $price);
    }

    public function scopeFree(Builder $builder, $free = true): Builder
    {
        return $builder->where('sales_free', '=', $free)->orWhere('price', '=', 0);
    }

    public function scopeEbook(Builder $builder): Builder
    {
        return $builder->type(EditionsEnums::Ebook);
    }

    public function scopeAudio(Builder $builder): Builder
    {
        return $builder->type(EditionsEnums::Audio);
    }

    public function scopePhysic(Builder $builder): Builder
    {
        return $builder->type(EditionsEnums::Physic);
    }

    public function scopeComics(Builder $builder): Builder
    {
        return $builder->type(EditionsEnums::Comics);
    }

    public function scopeType(Builder $builder, EditionsEnums $type): Builder
    {
        return $builder->where('type', '=', $type->value);
    }

    public function scopeStatus(Builder $builder, BookStatus $status): Builder
    {
        return $builder->where('status', '=', $status->value);
    }

    public function nextChapterSortOrder()
    {
        return ($this->chapters()->max('sort_order') ?? 0) + 1;
    }

    public function lengthRecount()
    {
        $this->chapters()->get()->each->lengthRecount();
        $this->length = (int) $this->chapters()->published()->sum('length');
        $this->save();
    }

    public function changeChaptersOrder(array $ids, ?array $order = null)
    {
        Db::transaction(function () use ($ids, $order) {
            $order ??= $this->chapters()->pluck((new Chapter())->getSortOrderColumn())->toArray();
            $this->chapters()->first()->setSortableOrder($ids, $order);
            $this->chapters()->get()->each->setNeighbours();
            $this->setFreeParts();
        });
    }

    public function setFreeParts()
    {
        Db::transaction(function () {
            if ($this->getOriginal('sales_free') || $this->getOriginal('status') === BookStatus::FROZEN) {
                $this->chapters()->published()->update(['sales_type' => ChapterSalesType::FREE]);
            } else {
                $this->chapters()->published()->limit($this->getOriginal('free_parts'))->update(['sales_type' => ChapterSalesType::FREE]);
//            $this->chapters()->offset($this->free_parts); ошибка?
                $this->chapters()->published()->get()->skip($this->getOriginal('free_parts'))->each->update(['sales_type' => ChapterSalesType::PAY]);
            }
        });
    }

    public function paginateContent()
    {
        $this->chapters()->get()->each->paginateContent();
    }
}
