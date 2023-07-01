<?php

declare(strict_types=1);

namespace App\Community\Components;

use App\Community\Models\News;
use App\Site\Components\Grid;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\AllowedSort;

class NewsGrid extends Grid
{
    public string $display = 'cards';

    protected function resourceName(): string
    {
        return 'news';
    }

    protected function columns(): iterable
    {
        return [
            [
                'key' => 'title',
                'label' => __res('achievement', 1),
                'sortBy' => 'title',
                'sortDirection' => 'asc',
            ],
            [
                'key' => 'game_avatar',
                'label' => false,
            ],
            [
                'key' => 'game',
                'label' => __res('game', 1),
            ],
            [
                'key' => 'created',
                'label' => __('validation.attributes.created_at'),
                'sortBy' => 'created',
                'sortDirection' => 'desc',
            ],
            // [
            //     'key' => 'published',
            //     'label' => 'Published',
            //     'sortBy' => 'published',
            //     'sortDirection' => 'desc',
            //     'class' => 'text-right',
            // ],
        ];
    }

    protected function displayOptions(): iterable
    {
        return [
            'cards',
        ];
    }

    protected function allowedSorts(): iterable
    {
        return [
            AllowedSort::field('created', 'created_at'),
        ];
    }

    /**
     * @return Builder<News>
     */
    protected function query(): Builder
    {
        $query = $this->resourceQuery();

        // $query->with('user');

        return $query;
    }

    protected function load(): ?LengthAwarePaginator
    {
        $results = parent::load();

        return $results;
    }
}
