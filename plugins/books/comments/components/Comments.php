<?php namespace Books\Comments\Components;

use Books\Comments\behaviors\Commentable;
use Books\Comments\Models\Comment;
use Books\Profile\Models\Profile;
use Closure;
use Cms\Classes\ComponentBase;
use Exception;
use October\Rain\Database\Model;
use RainLab\User\Facades\Auth;
use RainLab\User\Models\User;

/**
 * Comments Component
 *
 * @link https://docs.octobercms.com/3.x/extend/cms-components.html
 */
class Comments extends ComponentBase
{
    protected Model $model;
    protected ?User $user;
    protected Profile $owner;

    public function componentDetails()
    {
        return [
            'name' => 'Comments Component',
            'description' => 'No description provided yet...'
        ];
    }

    /**
     * @link https://docs.octobercms.com/3.x/element/inspector-types.html
     */
    public function defineProperties()
    {
        return [];
    }

    public function init()
    {
        $this->user = Auth::getUser();
    }

    public function prepareVals()
    {
        foreach ($this->vals() as $key => $val) {
            $this->page[$key] = $val;
        }
        $this->page['comments'] = $this->queryComments()->get()->toNested();
        $this->page['comments_count'] = $this->queryComments()->count();
    }

    public function vals(): array
    {
        return [
            'user' => $this->user,
            'owner' => $this->owner
        ];

    }

    public function queryComments()
    {
        return $this->model->comments()->with(['profile.avatar', 'children']);
    }

    /**
     * @throws Exception
     */
    public function bindModel(Closure|Model $model)
    {
        if (is_callable($model)) {
            $model = $model();
        }

        $this->model = $model;

        if (!$this->model->isClassExtendedWith(Commentable::class)) {
            throw new Exception(get_class($this->model) . ' must be extended with ' . Commentable::class . ' behavior.');
        }
    }

    public function bindModelOwner(Closure|Profile $model)
    {
        if (is_callable($model)) {
            $model = $model();
        }
        $this->owner = $model;
        $this->prepareVals();

    }

    public function onComment()
    {
        if (!$this->user) {
            return;
        }
        $payload = post();
        if (!$this->model->comments()->find(post('parent_id'))) {
            unset($payload['parent_id']);
        }
        $comment = $this->model->addComment($this->user, $payload);

        $this->prepareVals();
        return [
            '.comments-spawn' => $this->renderPartial('@default')
        ];
    }

    public function onEdit(){}
    public function onRemove(){}
}
