<?php

declare(strict_types=1);

namespace App\Platform\Components;

use App\Components\Grid;
use App\Platform\Models\Game;
use App\Platform\Models\System;
use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\AllowedSort;

class GameGrid extends Grid
{
    public bool $updateQuery = true;

    public ?int $systemId = null;

    public ?System $system = null;

    protected function resourceName(): string
    {
        return 'game';
    }

    protected function columns(): iterable
    {
        return [
            [
                'key' => 'image',
            ],
            [
                'key' => 'title',
                'label' => __res('game', 1),
                'sortBy' => 'title',
            ],
            // [
            //     'key' => 'system_icon',
            // ],
            // [
            //     'key' => 'system',
            //     'label' => 'System',
            // ],
            [
                'key' => 'achievements_count',
                'label' => __res('achievement'),
                'sortBy' => '-achievements',
            ],
            // [
            //     'key' => 'points_total',
            //     'label' => __res('point'),
            //     'sortBy' => '-points',
            // ],
            // [
            //     'key' => 'points_weighted',
            //     'label' => 'Points Ratio',
            //     'sortBy' => '-points_weighted',
            // ],
            // [
            //     'key' => 'leaderboards_count',
            //     'label' => __res('leaderboard'),
            //     'sortBy' => '-leaderboards',
            // ],
        ];
    }

    protected function defaultSort(): array|string|AllowedSort
    {
        return AllowedSort::field('-created', 'created_at');
    }

    protected function allowedSorts(): iterable
    {
        return [
            'title',
            AllowedSort::field('achievements', 'achievements_count'),
            AllowedSort::field('leaderboards', 'leaderboards_count'),
            // AllowedSort::field('points', 'points_total'),
            AllowedSort::field('created', 'created_at'),
        ];
    }

    /**
     * @return Builder<Game>
     */
    protected function query(): Builder
    {
        $query = parent::query();

        $query->with([
            'system',
            'media',
        ]);

        $query->withCount([
            'achievements',
            'leaderboards',
        ]);

        /** @var System|null $system */
        $system = System::find($this->systemId);

        if ($system) {
            $this->system = $system;
            $query->where('games.system_id', $this->system->getAttribute('id'));
        }

        return $query;
    }

    public function mount(?int $systemId = null): void
    {
        $this->systemId = $systemId;
    }

    protected function authorizeGrid(): void
    {
        $this->authorize('viewAny', $this->resourceClass($this->resourceName()));

        if ($this->system) {
            $this->authorize('view', $this->system);
        }
    }
}
