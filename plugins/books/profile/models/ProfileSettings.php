<?php namespace Books\Profile\Models;

use October\Rain\Database\Builder;
use Books\User\Models\Settings;
use Books\User\Classes\UserSettingsEnum;

/**
 * ProfileSettings Model
 */
class ProfileSettings extends Settings
{
    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {

        static::addGlobalScope('profilable', function (Builder $builder) {
            $builder->whereIn('setting_id', collect(UserSettingsEnum::profilable())->pluck('value')->toArray());
        });
    }
}