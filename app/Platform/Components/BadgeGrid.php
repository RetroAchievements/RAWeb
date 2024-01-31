<?php

declare(strict_types=1);

namespace App\Platform\Components;

use App\Components\Grid;
use Spatie\QueryBuilder\AllowedSort;

class BadgeGrid extends Grid
{
    public bool $updateQuery = true;

    protected function resourceName(): string
    {
        return 'badge';
    }

    protected function columns(): iterable
    {
        return [
            //     [
            //         'key' => 'image',
            //     ],
            //     [
            //         'key' => 'title',
            //         'label' => 'Title',
            //         'sortBy' => 'title',
            //         'sortDirection' => 'asc',
            //     ],
            //     [
            //         'key' => 'description',
            //         'label' => 'Description',
            //         'sortBy' => 'description',
            //     ],
            //     [
            //         'key' => 'points',
            //         'label' => 'Points',
            //         'sortBy' => 'points',
            //         'sortDirection' => 'desc',
            //     ],
            //     // [
            //     //     'key' => 'points_weighted',
            //     //     'label' => 'Points Ratio',
            //     //     'sortBy' => 'points_weighted',
            //     //     'sortDirection' => 'desc',
            //     // ],
            //     [
            //         'key' => 'user',
            //         'label' => 'Author',
            //     ],
            //     [
            //         'key' => 'game_avatar',
            //     ],
            //     [
            //         'key' => 'game',
            //         'label' => 'Game',
            //     ],
            //     [
            //         'key' => 'created',
            //         'label' => __('validation.attributes.created_at'),
            //         'sortBy' => 'created',
            //         'sortDirection' => 'desc',
            //     ],
        ];
    }

    protected function allowedSorts(): iterable
    {
        return [
            'title',
            'points',
            AllowedSort::field('created', 'created_at'),
        ];
    }
}
