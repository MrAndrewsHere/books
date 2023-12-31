<?php

namespace Books\Book\Classes;

use Books\Book\Classes\Enums\ChapterSalesType;
use Books\Book\Classes\Enums\ChapterStatus;
use Books\Book\Classes\Exceptions\UnknownFormatException;
use Books\Book\Models\Chapter;
use Books\Book\Models\Edition;
use Books\Book\Models\Pagination;
use Carbon\Carbon;
use Closure;
use Db;
use DOMDocument;
use Event;
use Exception;
use Illuminate\Support\Collection;
use Str;
use ValidationException;

class ChapterService
{
    public function __construct(protected Chapter $chapter, protected ?Edition $edition = null)
    {
        if (!$this->isNew() && !$this->edition?->exists) {
            $this->edition = $this->chapter->edition;
        }
    }

    public function isNew(): bool
    {
        return !$this->chapter->exists;
    }

    /**
     * @param Edition $edition
     * @return ChapterService
     */
    public function setEdition(Edition $edition): static
    {
        $this->edition = $edition;

        return $this;
    }

    /**
     * @throws UnknownFormatException
     * @throws Exception
     */
    public function from(mixed $payload): ?Chapter
    {
        if ($payload instanceof \Tizis\FB2\Model\Chapter) {
            if ((int)$this->parseContentToCollection($payload->getContent())->sum('length')) {
                return $this->create([
                    'title' => $payload->getTitle(),
                    'content' => $payload->getContent(),
                    'status' => ChapterStatus::PUBLISHED,
                ]);
            }

            return null;
        }

        if (is_array($payload) || $payload instanceof Collection) {
            return $this->{$this->isNew() ? 'create' : 'update'}(is_array($payload) ? $payload : $payload->toArray());
        }
        throw new UnknownFormatException();
    }

    /**
     * @throws Exception
     */
    protected function create(array $data): Chapter
    {
        if (!$this->edition->id) {
            throw new Exception('Edition required.');
        }
        $this->chapter->fill($this->dataPrepare($data));
        $this->chapter->sort_order = $this->edition->nextChapterSortOrder();
        $this->chapter->sales_type ??= ChapterSalesType::PAY;
        $this->chapter['edition_id'] = $this->edition->id;
        $this->chapter->save();

        Event::fire('books.chapter.created', [$this->chapter]);

        return $this->chapter;
    }

    protected function update(array $data): Chapter
    {
        return Db::transaction(function () use ($data) {
            $data = $this->dataPrepare($data);
            $this->chapter->fill($data);
            $this->chapter->save();
            Event::fire('books.chapter.updated', [$this->chapter]);

            return $this->chapter;
        });
    }

    public function delete(): void
    {
        if (!$this->chapter->edition->editAllowed()) {
            throw new ValidationException(['chapter' => 'В данный момент Вы не можете удалять главы книг.']);
        }
        $this->chapter->delete();
    }

    public function dataPrepare(array|Collection $data): array
    {
        if (!$this->edition->editAllowed()) {
            throw new ValidationException(['edition' => 'Для этой книги запрещено редактирование глав.']);
        }
        $data = collect($data);

        if ($data->has('status')) {
            $data['status'] = $data['status'] instanceof ChapterStatus ? $data['status'] : (ChapterStatus::tryFrom($data->get('status')) ?? ChapterStatus::DRAFT);

            if ($data->get('status') instanceof ChapterStatus) {
                switch ($data->get('status')) {
                    case ChapterStatus::PUBLISHED:

                        $data['published_at'] = Carbon::now();
                        break;

                    case ChapterStatus::PLANNED:
                        if (!($data->get('published_at') instanceof Carbon)) {
                            throw new ValidationException(['published_at' => 'Не верный формат даты публикации.']);
                        }
                        $data['published_at'] = $data['published_at']->copy()->setMinutes(0)->setSeconds(0);
                        break;

                    default:

                        $data['published_at'] = null;
                }
            } else {
                $data->forget('status');
            }
        }

        if ($data->has('content')) {
            $data['new_content'] = $data['content'];
            $data->forget('content');
        }

        return $data->toArray();
    }

    public function getPaginationLinks(int $page = 1)
    {
        if (!$this->isNew()) {
            $pagination = $this->chapter->pagination;
            $links = $pagination->map(function ($item) use ($pagination, $page) {
                if (in_array($item->page, [
                    $page,
                    $page + 1,
                    $page - 1,
                    $pagination->first()->page,
                    $pagination->last()->page,
                ])) {
                    return $item;
                }

                return null;
            });

            return $links->filter(function ($value, $key) use ($links) {
                return $value || ((bool)$links[$key + 1] ?? false);
            })->values();
        }

        return null;
    }

    public function paginate()
    {
        $chunks = $this->chunkContent();
        $pages = $chunks->map(function ($chunk, $index) {
            return new Pagination(
                [
                    'page' => $index + 1,
                    'new_content' => $chunk->pluck('html')->join(''),
                    'length' => $chunk->sum('length'),
                ]
            );
        });
        $pagination = $pages->map(function ($paginator) {
            $page = $this->chapter->pagination()->firstOrCreate(['page' => $paginator->page], ['length' => $paginator->length]);
            $page->fill($paginator->toArray());
            $page->save();

            return $page;
        });
        $this->chapter->pagination()->whereNotIn('id', $pagination->pluck('id'))->delete();
        $this->chapter->pagination()->get()->each->setNeighbours();
        $this->chapter->lengthRecount();
        Event::fire('books.chapter.paginated');
    }

    public function chunkContent(): Collection
    {
        return $this->parseContentToCollection($this->chapter->getContent()->body)->chunkWhile(function ($value, $key, $chunk) {
            return $chunk->sum('length') + $value['length'] <= Pagination::RECOMMEND_MAX_LENGTH;
        });
    }

    public function parseContentToCollection(string $content): Collection
    {
        $content = trim($content);
        if (!$content) {
            return collect([
                'html' => '',
                'length' => 0
            ]);
        }
        $dom = (new DOMDocument());
        libxml_use_internal_errors(true);
        $dom->loadHTML(mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8'));
        $root = $dom->getElementsByTagName('body')[0];

        return collect($root->childNodes)->map(fn($node) => [
            'html' => $dom->saveHTML($node),
            'length' => self::countContentLength($node->textContent),
        ]);
    }

    public static function countContentLength(string $str): bool|int
    {
        return iconv_strlen(Str::squish($str));
    }

    public function publish($forceFireEvent = true): Closure
    {
        $event = Db::transaction(function () {
            $this->chapter->fill([
                'status' => ChapterStatus::PUBLISHED,
                'published_at' => Carbon::now(),
            ]);
            $this->chapter->save();

            return fn() => Event::fire('books.chapter.published', [$this->chapter]);
        });
        if ($forceFireEvent) {
            $event();
        }

        return $event;
    }

    public static function audit(): void
    {
        Db::transaction(function () {
            return Chapter::query()
                ->planned()
                ->where('published_at', '<=', Carbon::now())
                ->lockForUpdate()
                ->get()
                ->map(function ($chapter) {
                    return $chapter->service()->publish(forceFireEvent: false);
                });
        })->map(fn($callback) => is_callable($callback) ? $callback() : $callback);
    }
}
