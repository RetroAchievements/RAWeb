<?php

declare(strict_types=1);

namespace App\Api\V2\Tickets;

use App\Models\Achievement;
use App\Models\Leaderboard;
use App\Models\Ticket;
use Illuminate\Database\Eloquent\Builder;
use LaravelJsonApi\Core\Exceptions\JsonApiException;
use LaravelJsonApi\Eloquent\Contracts\Filter;

final class GameIdFilter implements Filter
{
    public function key(): string
    {
        return 'gameId';
    }

    public function isSingular(): bool
    {
        return true;
    }

    /**
     * @param Builder<Ticket> $query
     * @return Builder<Ticket>
     */
    public function apply($query, $value)
    {
        $raw = trim((string) $value);

        if ($raw === '') {
            return $query;
        }

        if (!ctype_digit($raw)) {
            throw JsonApiException::error([
                'status' => '400',
                'code' => 'invalid_filter',
                'title' => 'Invalid Filter',
                'detail' => "Invalid game id [{$raw}].",
            ]);
        }

        $gameId = (int) $raw;

        // inline whereHasMorph to avoid hydrating a Game object just to read its id
        return $query->whereHasMorph(
            'ticketable',
            [Achievement::class, Leaderboard::class],
            fn (Builder $q) => $q->where('game_id', $gameId),
        );
    }
}
