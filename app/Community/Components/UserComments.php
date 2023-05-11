<?php

declare(strict_types=1);

namespace App\Community\Components;

use App\Community\Models\UserComment;
use App\Site\Components\Grid;
use App\Site\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class UserComments extends Grid
{
    public ?int $userId = null;

    private ?User $user = null;

    protected array $pageSizes = [
        10,
    ];

    protected function resourceName(): string
    {
        return 'user.comment';
    }

    public function viewData(): array
    {
        return array_merge(
            parent::viewData(),
            [
                'user' => $this->user,
            ]
        );
    }

    /**
     * @return Builder<UserComment>
     */
    protected function query(): Builder
    {
        /** @var User $user */
        $user = User::findOrFail($this->userId);

        $this->user = $user;

        $query = $this->user->comments()->getQuery();

        $query->with('user');

        if ($this->take) {
            $this->pageSizes = [$this->take];
            $this->pageSize($this->take);
        }

        return $query;
    }

    protected function authorizeGrid(): void
    {
        $this->authorize('view', $this->user);
        $this->authorize('viewAny', [UserComment::class, $this->user]);
    }

    protected function load(): ?LengthAwarePaginator
    {
        parent::load();

        return $this->results;
    }
}
