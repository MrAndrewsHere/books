<?php

namespace Books\Notifications\Classes\Events;

use Books\Notifications\Classes\NotificationTypeEnum;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class CommentCreated extends BaseEvent
{
    public string $eventName = 'Комментарий к книге';
    public string $eventDescription = 'Пользователь оставил комментарий к книге автора.';

    /**
     * @return array
     */
    public static function defaultParams(): array
    {
        return [
            'type' => NotificationTypeEnum::BOOKS->value,
            'icon' => 'pencil-line-stroked-24',
            'template' => 'comment_created',
        ];
    }

    /**
     * @param array $args
     * @param $eventName
     * @return array
     */
    public static function makeParamsFromEvent(array $args, $eventName = null): array
    {
        return array_merge(
            static::defaultParams(),
            [
                'comment' => Arr::get($args, 0),
                'recipients' => static::getRecipients($args),
            ],
        );
    }

    /**
     * @param array $args
     * @return Collection|null
     */
    public static function getRecipients(array $args): ?Collection
    {
        $comment = Arr::get($args, 0);

        // README: возвращаем именно такую коллекцию, а не collect() ибо во втором случае ошибка сериализации
        return new \October\Rain\Database\Collection([
            $comment->commentable?->author?->profile,
        ]);
    }
}
