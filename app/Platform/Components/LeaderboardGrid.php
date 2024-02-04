<?php

declare(strict_types=1);

namespace App\Platform\Components;

use App\Components\Grid;

class LeaderboardGrid extends Grid
{
    public bool $updateQuery = true;

    protected function resourceName(): string
    {
        return 'leaderboard';
    }

    protected function columns(): iterable
    {
        return [
            [
                'key' => 'image',
                'label' => false,
            ],
            [
                'key' => 'title',
                'label' => 'Achievement',
                'sortBy' => 'title',
                'sortDirection' => 'asc',
            ],
            // [
            //     'key' => 'description',
            //     'label' => 'Description',
            //     'sortBy' => 'description',
            // ],
            [
                'key' => 'points',
                'label' => 'Points',
                'sortBy' => 'points',
                'sortDirection' => 'desc',
            ],
            // [
            //     'key' => 'points_weighted',
            //     'label' => 'Points Ratio',
            //     'sortBy' => 'points_weighted',
            //     'sortDirection' => 'desc',
            // ],
            // [
            //     'key' => 'user',
            //     'label' => 'Author',
            // ],
            // [
            //     'key' => 'user_count',
            //     'label' => 'Authors',
            // ],
            [
                'key' => 'game_avatar',
                'label' => false,
            ],
            [
                'key' => 'game',
                'label' => 'Game',
            ],
            [
                'key' => 'created',
                'label' => __('validation.attributes.created_at'),
                'sortBy' => 'created',
                'sortDirection' => 'desc',
            ],
        ];
    }
}
