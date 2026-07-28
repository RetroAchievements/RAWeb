<?php

declare(strict_types=1);

namespace App\Api\V2\Tickets;

use App\Models\Ticket;
use App\Platform\Enums\TicketableType;
use Illuminate\Database\Eloquent\Builder;
use LaravelJsonApi\Core\Exceptions\JsonApiException;
use LaravelJsonApi\Eloquent\Contracts\Filter;

final class AchievementIdFilter implements Filter
{
    public function key(): string
    {
        return 'achievementId';
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
        $ids = $this->parseIds($value);

        // both predicates are needed to prevent leaderboard tickets with colliding ids from leaking
        return $query
            ->where('ticketable_type', TicketableType::Achievement->value)
            ->whereIn('ticketable_id', $ids);
    }

    /**
     * @return list<int>
     */
    private function parseIds(mixed $value): array
    {
        if (is_array($value)) {
            $value = implode(',', $value);
        }

        $raw = collect(explode(',', (string) $value))
            ->map(fn (string $v) => trim($v))
            ->filter()
            ->unique()
            ->values()
            ->all();

        return collect($raw)
            ->map(function (string $v) {
                if (!ctype_digit($v)) {
                    throw JsonApiException::error([
                        'status' => '400',
                        'code' => 'invalid_filter',
                        'title' => 'Invalid Filter',
                        'detail' => "Invalid achievement id [{$v}].",
                    ]);
                }

                return (int) $v;
            })
            ->values()
            ->all();
    }
}
