<?php

declare(strict_types=1);

namespace App\Community\Components;

use App\Community\Models\AchievementComment;
use App\Components\Grid;
use App\Platform\Models\Achievement;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class AchievementComments extends Grid
{
    public ?int $achievementId = null;

    private ?Achievement $achievement = null;

    protected array $pageSizes = [
        10,
    ];

    protected function resourceName(): string
    {
        return 'achievement.comment';
    }

    public function mount(int $achievementId, ?int $take = null): void
    {
        $this->achievementId = $achievementId;
        $this->take = $take;
        $this->updateQuery = !$take;
    }

    public function viewData(): array
    {
        return array_merge(
            parent::viewData(),
            [
                'achievement' => $this->achievement,
            ]
        );
    }

    /**
     * @return Builder<AchievementComment>
     */
    protected function query(): Builder
    {
        /** @var Achievement $achievement */
        $achievement = Achievement::findOrFail($this->achievementId);

        $this->achievement = $achievement;

        $query = $this->achievement->comments()->getQuery();

        return $query;
    }

    protected function authorizeGrid(): void
    {
        $this->authorize('view', $this->achievement);
        $this->authorize('viewAny', [AchievementComment::class, $this->achievement]);
    }

    protected function load(): ?LengthAwarePaginator
    {
        parent::load();

        return $this->results;
    }
}
