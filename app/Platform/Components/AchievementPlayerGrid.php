<?php

declare(strict_types=1);

namespace App\Platform\Components;

use App\Site\Components\Grid;
use Illuminate\Database\Eloquent\Builder;

class AchievementPlayerGrid extends Grid
{
    public ?int $achievementId = null;

    public string $display = 'list';

    protected array $pageSizes = [
        10,
        25,
        50,
    ];

    protected function resourceName(): string
    {
        return 'player-achievement';
    }

    protected function columns(): iterable
    {
        return [
            [
                'key' => 'user_id',
            ],
            [
                'key' => 'hardcore',
                'label' => __('Hardcore'),
            ],
            [
                'key' => 'unlocked',
                'label' => __('Unlocked'),
            ],
        ];
    }

    protected function query(): Builder
    {
        $query = $this->resourceQuery()->where('achievement_id', $this->achievementId);

        return $query;
    }

    protected function authorizeGrid(): void
    {
        $this->authorize('viewAny', $this->resourceClass('achievement'));
    }
}
