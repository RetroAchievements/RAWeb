<?php

declare(strict_types=1);

namespace App\Community\Components;

use App\Community\Models\UserActivity;
use App\Site\Components\Grid;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Spatie\QueryBuilder\AllowedFilter;

class UserActivityFeed extends Grid
{
    public array $filter = [
        'users' => 'following',
    ];

    protected array $pageSizes = [
        25,
        50,
    ];

    protected function resourceName(): string
    {
        return 'user-activity';
    }

    // public function render()
    // {
    //     // TODO: finish refactoring
    //
    //     return view('components.user.activity')
    //         ->with('followingFiltered', false)
    //         ->with('userActivities', $this->loadDeferred());
    // }

    protected function view(): string
    {
        return 'components.user.activity';
    }

    public function allowedFilters(): iterable
    {
        return [
            AllowedFilter::callback('users', function (Builder $query, $value) {
                if (request()->user() && $value === 'following') {
                    $followingIds = request()->user()->following()->get(['id'])->pluck('id');
                    $followingIds[] = request()->user()->ID;
                    $query->whereIn('user_id', $followingIds);
                }
            }),
        ];
    }

    public function resetFilters(): void
    {
        $this->filter = [];
        $this->resetPage();
    }

    public function filterByFollowing(): void
    {
        $this->filter['users'] = 'following';
        $this->resetPage();
    }

    protected function load(): ?LengthAwarePaginator
    {
        parent::load();

        // @phpstan-ignore-next-line
        (new Collection($this->results->items()))->map(function (UserActivity $userActivity) {
            if ($userActivity->isAchievementActivity()) {
                $userActivity->achievement->load('game');
            }

            return $userActivity;
        });

        return $this->results;
    }
}
