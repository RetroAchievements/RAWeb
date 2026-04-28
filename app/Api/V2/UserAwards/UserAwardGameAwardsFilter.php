<?php

declare(strict_types=1);

namespace App\Api\V2\UserAwards;

use App\Models\PlayerBadge;
use Illuminate\Database\Eloquent\Builder;
use LaravelJsonApi\Core\Exceptions\JsonApiException;
use LaravelJsonApi\Eloquent\Contracts\Filter;

final class UserAwardGameAwardsFilter implements Filter
{
    public function key(): string
    {
        return 'gameAwards';
    }

    public function isSingular(): bool
    {
        return false;
    }

    /**
     * @param Builder<PlayerBadge> $query
     * @return Builder<PlayerBadge>
     */
    public function apply($query, $value)
    {
        $mode = trim((string) $value);

        return match ($mode) {
            '', 'all' => $query,
            'highest' => $query->highestGameAwardPerGame(),
            default => throw JsonApiException::error([
                'status' => '400',
                'code' => 'invalid_filter',
                'title' => 'Invalid Filter',
                'detail' => "Unknown game awards filter [{$mode}].",
            ]),
        };
    }
}
