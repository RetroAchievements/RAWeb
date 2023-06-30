<?php

declare(strict_types=1);

namespace App\Platform\Components;

use App\Platform\Models\PlayerSession;
use App\Site\Components\Grid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class GamePlayerGrid extends Grid
{
    public ?int $gameId = null;

    public string $display = 'list';

    protected array $pageSizes = [
        10,
        25,
        50,
    ];

    protected function resourceName(): string
    {
        return 'player-session';
    }

    protected function columns(): iterable
    {
        return [
            [
                'key' => 'user_id',
            ],
            [
                'key' => 'updated_at',
                'label' => __('When'),
            ],
            [
                'key' => 'rich_presence',
                'label' => __('Last Seen'),
            ],
        ];
    }

    /**
     * @return Builder<PlayerSession>
     */
    protected function query(): Builder
    {
        // find the latest session for each user who has played the game
        $latest_sessions = DB::table('player_sessions as ps3')
            ->select('ps3.user_id', DB::raw('max(ps3.rich_presence_updated_at) as max_date'))
            ->where('ps3.game_id', $this->gameId)
            ->groupBy('ps3.user_id');

        // build a query to return the models associated to the latest sessions
        $query = PlayerSession::select('player_sessions.user_id', 'rich_presence', 'rich_presence_updated_at')
            ->joinSub($latest_sessions, 'ps2', function ($join) {
                $join->on('player_sessions.user_id', '=', 'ps2.user_id')
                    ->on('player_sessions.rich_presence_updated_at', '=', 'ps2.max_date');
            })
            ->orderByDesc('rich_presence_updated_at');

        return $query;
    }

    protected function authorizeGrid(): void
    {
        $this->authorize('viewAny', $this->resourceClass('game'));
    }
}
