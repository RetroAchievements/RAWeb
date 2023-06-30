<?php

declare(strict_types=1);

namespace App\Platform\Components;

use App\Platform\Models\GameHash;
use App\Site\Components\Grid;
use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\AllowedSort;

class GameHashGrid extends Grid
{
    public bool $updateQuery = true;

    protected function resourceName(): string
    {
        return 'game-hash';
    }

    protected function columns(): iterable
    {
        return [
            [
                'key' => 'hash',
                'label' => __('Hash'),
            ],
            [
                'key' => 'system',
                'label' => __res('system', 1),
            ],
            [
                'key' => 'games',
                'label' => __res('game'),
            ],
            [
                'key' => 'created',
                'label' => __('validation.attributes.created_at'),
                'sortBy' => '-created',
            ],
        ];
    }

    protected function defaultSort(): array|string|AllowedSort
    {
        return AllowedSort::field('-created', 'created_at');
    }

    protected function allowedSorts(): iterable
    {
        return [
            AllowedSort::field('created', 'created_at'),
        ];
    }

    /**
     * @return Builder<GameHash>
     */
    protected function query(): Builder
    {
        $query = parent::query();

        $query->with([
            'system',
            'gameHashSets.game',
        ]);

        $query->withCount([
            'system',
        ]);

        return $query;
    }
}
