<?php

declare(strict_types=1);

namespace App\Api\V2\Tickets;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use LaravelJsonApi\Eloquent\Contracts\Filter;

/**
 * Filters tickets by a numeric user FK using public ULIDs. V2 identifiers
 * are ULIDs but `reporter_id`/`resolver_id` are numeric.
 *
 * This resolves once via `whereIn('ulid', ...)` and applies `whereIn` on the column.
 *
 * Unknown ULIDs yield empty.
 */
final class UserUlidFilter implements Filter
{
    public function __construct(
        private readonly string $key,
        private readonly string $column,
    ) {
    }

    public function key(): string
    {
        return $this->key;
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
        $ulids = $this->parseUlids($value);

        if ($ulids === []) {
            return $query;
        }

        $userIds = User::query()
            ->whereIn('ulid', $ulids)
            ->pluck('id')
            ->all();

        if ($userIds === []) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn($this->column, $userIds);
    }

    /**
     * @return list<string>
     */
    private function parseUlids(mixed $value): array
    {
        if (is_array($value)) {
            $value = implode(',', $value);
        }

        return collect(explode(',', (string) $value))
            ->map(fn (string $v) => trim($v))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
