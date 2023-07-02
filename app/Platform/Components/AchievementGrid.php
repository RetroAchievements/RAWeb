<?php

declare(strict_types=1);

namespace App\Platform\Components;

use App\Platform\Models\Achievement;
use App\Platform\Models\System;
use App\Site\Components\Grid;
use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\AllowedSort;

class AchievementGrid extends Grid
{
    public bool $updateQuery = true;

    public ?int $systemId = null;

    private ?System $system = null;

    protected function resourceName(): string
    {
        return 'achievement';
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
                'label' => __res('achievement', 1),
                'sortBy' => 'title',
            ],
            // [
            //     'key' => 'description',
            //     'label' => 'Description',
            //     'sortBy' => 'description',
            // ],
            [
                'key' => 'points',
                'label' => __res('point'),
                'sortBy' => '-points',
            ],
            // [
            //     'key' => 'points_weighted',
            //     'label' => 'Points Ratio',
            //     'sortBy' => '-points_weighted',
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
                'label' => __res('game', 1),
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
            'title',
            'points',
            AllowedSort::field('created', 'created_at'),
        ];
    }

    public function mount(?int $systemId = null): void
    {
        $this->systemId = $systemId;
    }

    /**
     * @return Builder<Achievement>
     */
    protected function query(): Builder
    {
        $query = parent::query();

        $query->with('game', 'user');

        /** @var System|null $system */
        $system = System::find($this->systemId);

        if ($system) {
            $this->system = $system;
            $query->whereHas('game', function ($query) {
                $query->where('games.system_id', $this->system->id);
            });
        }

        return $query;
    }

    protected function authorizeGrid(): void
    {
        $this->authorize('viewAny', $this->resourceClass($this->resourceName()));

        if ($this->system) {
            $this->authorize('view', $this->system);
        }
    }
}
