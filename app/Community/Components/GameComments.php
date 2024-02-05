<?php

declare(strict_types=1);

namespace App\Community\Components;

use App\Community\Models\GameComment;
use App\Components\Grid;
use App\Models\Game;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class GameComments extends Grid
{
    public ?int $gameId = null;

    private ?Game $game = null;

    protected array $pageSizes = [
        10,
    ];

    protected function resourceName(): string
    {
        return 'game.comment';
    }

    public function mount(int $gameId, ?int $take = null): void
    {
        $this->gameId = $gameId;
        $this->take = $take;
        $this->updateQuery = !$take;
    }

    public function viewData(): array
    {
        return array_merge(
            parent::viewData(),
            [
                'game' => $this->game,
            ]
        );
    }

    /**
     * @return Builder<GameComment>
     */
    protected function query(): Builder
    {
        /** @var Game $game */
        $game = Game::findOrFail($this->gameId);

        $this->game = $game;

        $query = $this->game->comments()->getQuery();

        $query->with('user');

        if ($this->take) {
            $this->pageSizes = [$this->take];
            $this->pageSize($this->take);
        }

        return $query;
    }

    protected function authorizeGrid(): void
    {
        $this->authorize('view', $this->game);
        $this->authorize('viewAny', [GameComment::class, $this->game]);
    }

    protected function load(): ?LengthAwarePaginator
    {
        parent::load();

        return $this->results;
    }
}
