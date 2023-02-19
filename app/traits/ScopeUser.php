<?php

namespace App\traits;

use October\Rain\Database\Builder;
use RainLab\User\Models\User;

trait ScopeUser
{
    public function scopeUser(Builder $builder, User $user): Builder
    {
        return $builder->where('user_id', '=', $user->id);
    }
}
