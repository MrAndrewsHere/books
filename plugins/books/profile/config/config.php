<?php

use Books\Reviews\Models\Review;
use Books\User\Models\Settings;

return [
    'profileable' => [
        Review::class,
        Settings::class,
    ],
];
