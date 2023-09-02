<?php

use App\Community\Enums\UserGameListType;

return [
    UserGameListType::AchievementSetRequest => [
        'added' => __('Set requested'),
        'removed' => __('Set request withdrawn'),
    ],
    UserGameListType::Play => [
        'add' => __('Add to Want to Play list'),
        'added' => __('Added to Want to Play list'),
        'name' => __('Want to Play'),
        'remove' => __('Remove from Want to Play list'),
        'removed' => __('Removed from Want to Play list'),
    ],
];
