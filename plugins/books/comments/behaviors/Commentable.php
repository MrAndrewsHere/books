<?php

namespace Books\Comments\behaviors;

use Books\Book\Models\Book;
use Books\Comments\Models\Comment;
use Books\Profile\Models\Profile;
use October\Rain\Database\Builder;
use October\Rain\Database\Model;
use October\Rain\Extension\ExtensionBase;
use RainLab\User\Models\User;

class Commentable extends ExtensionBase
{
    public function __construct(protected Model $model)
    {
        $this->model->morphMany['comments'] = [Comment::class, 'name' => 'commentable'];
    }

    public function isCommentAllowed(): bool
    {
        return true;
    }

    public function addComment(User $user, array $payload)
    {
        if (! $this->model->isCommentAllowed()) {
            return false;
        }
        $payload['user_id'] = $user->id;
        $comment = $this->model->comments()->create($payload);
        $this->after($comment);

        return $comment;
    }

    public function deleteComment(Comment $comment)
    {
        $comment->delete();
        $this->after($comment);
    }

    protected function after($comment)
    {
        if ($comment->commentable instanceof Book) {
            $comment->commentable->rater()->comments()->queue();
        }
    }

    public function scopeCommentsCount(Builder $builder, ?Profile $profile): Builder
    {
        return $builder->withCount(['comments' => function (Builder $comments) use ($profile) {
            return $comments->when($profile?->exists, function (Builder $c) use ($profile) {
                return $c->whereDoesntHave('profile', function (Builder $p) use ($profile) {
                    return $p->whereIn((new Profile())->getTable().'.id', [$profile->id]);
                });
            });
        }]);
    }
}
