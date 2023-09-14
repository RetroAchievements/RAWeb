<?php

use App\Community\Enums\UserGameListType;

return [
    UserGameListType::AchievementSetRequest => [
        'added' => __('Set requested'),
        'removed' => __('Set request withdrawn'),
    ],
    UserGameListType::Play => [
        'add' => __('Add to Want to Play Games'),
        'added' => __('Added to Want to Play Games'),
        'name' => __('Want to Play'),
        'remove' => __('Remove from Want to Play Games'),
        'removed' => __('Removed from Want to Play Games'),
    ],
    UserGameListType::Develop => [
        'add' => __('Add to Want to Develop Games'),
        'added' => __('Added to Want to Develop Games'),
        'name' => __('Want to Develop'),
        'remove' => __('Remove from Want to Develop Games'),
        'removed' => __('Removed from Want to Develop Games'),
    ],
];
