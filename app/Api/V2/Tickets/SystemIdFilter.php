<?php

declare(strict_types=1);

namespace App\Api\V2\Tickets;

use App\Models\Achievement;
use App\Models\Leaderboard;
use App\Models\Ticket;
use Illuminate\Database\Eloquent\Builder;
use LaravelJsonApi\Core\Exceptions\JsonApiException;
use LaravelJsonApi\Eloquent\Contracts\Filter;

final class SystemIdFilter implements Filter
{
    public function key(): string
    {
        return 'systemId';
    }

    public function isSingular(): bool
    {
        return false;
    }

    /**
     * @param Builder<Ticket> $query
     * @return Builder<Ticket>
     */
    public function apply($query, $value)
    {
        $systemIds = $this->parseIds($value);

        if ($systemIds === []) {
            return $query;
        }

        return $query->whereHasMorph(
            'ticketable',
            [Achievement::class, Leaderboard::class],
            fn (Builder $ticketable) => $ticketable->whereHas('game', fn (Builder $game) => $game->whereIn('system_id', $systemIds)),
        );
    }

    /**
     * @return list<int>
     */
    private function parseIds(mixed $value): array
    {
        if (is_array($value)) {
            $value = implode(',', $value);
        }

        return collect(explode(',', (string) $value))
            ->map(fn (string $v) => trim($v))
            ->filter()
            ->unique()
            ->map(function (string $v) {
                if (!ctype_digit($v)) {
                    throw JsonApiException::error([
                        'status' => '400',
                        'code' => 'invalid_filter',
                        'title' => 'Invalid Filter',
                        'detail' => "Invalid system id [{$v}].",
                    ]);
                }

                return (int) $v;
            })
            ->values()
            ->all();
    }
}
