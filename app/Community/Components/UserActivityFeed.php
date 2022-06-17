<?php

declare(strict_types=1);

namespace App\Community\Components;

use App\Community\Models\UserActivity;
use App\Site\Components\Grid;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\AllowedFilter;

class UserActivityFeed extends Grid
{
    public array $filter = [
        'users' => 'friends',
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
    //         ->with('friendsFiltered', false)
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
                if (request()->user() && $value === 'friends') {
                    $friendsIds = request()->user()->friends()->get(['id'])->pluck('id');
                    $friendsIds[] = request()->user()->id;
                    $query->whereIn('user_id', $friendsIds);
                }
            }),
        ];
    }

    public function resetFilters(): void
    {
        $this->filter = [];
        $this->resetPage();
    }

    public function filterByFriends(): void
    {
        $this->filter['users'] = 'friends';
        $this->resetPage();
    }

    protected function load(): ?LengthAwarePaginator
    {
        parent::load();

        collect($this->results->items())->map(function (UserActivity $userActivity) {
            if ($userActivity->isAchievementActivity()) {
                $userActivity->achievement->load('game');
            }

            return $userActivity;
        });

        return $this->results;
    }
}
