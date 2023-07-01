<?php

declare(strict_types=1);

namespace App\Site\Components;

use App\Site\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\AllowedSort;

class UserGrid extends Grid
{
    public bool $updateQuery = true;

    protected function resourceName(): string
    {
        return 'user';
    }

    protected function columns(): iterable
    {
        // $filter = $this->request()->query('filter');
        $sort = $this->request()->query('sort');

        return collect([
            // [
            //     'key' => 'rank',
            //     'label' => 'Rank',
            //     'sortBy' => '-points',
            //     'displayOnSort' => 'points',
            // ],
            [
                'key' => 'avatar',
                'label' => false,
            ],
            [
                'key' => 'username',
                'label' => 'User',
                'sortBy' => 'name',
            ],
            [
                'key' => 'points',
                'label' => 'Points',
                'sortBy' => '-points',
            ],
            [
                'key' => 'achievements',
                'label' => 'Achievements',
                'sortBy' => '-achievements',
            ],
        ])
            ->map(function ($column) use ($sort) {
                $column['active'] = $column['key'] === $sort;
                // $column['visible'] = $column['key'] === $sort;
                return $column;
            });
        // ->filter(fn($column) => $column['visible']);
    }

    // protected function defaultSort(): string
    // {
    //     return '-points';
    // }

    protected function allowedSorts(): array
    {
        return [
            AllowedSort::field('name', 'display_name'),
            AllowedSort::field('points', 'points_total'),
            AllowedSort::field('achievements', 'achievements_unlocked'),
        ];
    }

    protected function allowedFilters(): array
    {
        return [
        ];
    }

    /**
     * @return Builder<User>
     */
    protected function query(): Builder
    {
        $query = parent::query();

        /*
         * TODO: eager load, transform columns, etc
         */
        $query->withCount('playerAchievements as achievements_unlocked');

        // ->where('banned_at', null)
        // ->withCount('playerAchievements as achievements_unlocked');
        // ->search($this->search)

        return $query;
    }
}
