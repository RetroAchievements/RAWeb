<?php

declare(strict_types=1);

namespace App\Api\V2\EventAchievements;

use App\Models\EventAchievement;
use Illuminate\Database\Eloquent\Builder;
use LaravelJsonApi\Core\Exceptions\JsonApiException;
use LaravelJsonApi\Eloquent\Contracts\Filter;

final class EventAchievementEventIdFilter implements Filter
{
    public function key(): string
    {
        return 'eventId';
    }

    public function isSingular(): bool
    {
        return false;
    }

    /**
     * @param Builder<EventAchievement> $query
     * @return Builder<EventAchievement>
     */
    public function apply($query, $value)
    {
        return $query->forEventIds($this->parseEventIds((string) $value));
    }

    /**
     * @return list<int>
     */
    private function parseEventIds(string $value): array
    {
        $eventIds = collect(explode(',', $value))
            ->map(fn (string $eventId): string => trim($eventId))
            ->filter()
            ->unique()
            ->values();

        if ($eventIds->isEmpty()) {
            throw $this->invalidFilter($value);
        }

        return $eventIds
            ->map(function (string $eventId) use ($value): int {
                if (!ctype_digit($eventId) || (int) $eventId < 1) {
                    throw $this->invalidFilter($value);
                }

                return (int) $eventId;
            })
            ->all();
    }

    private function invalidFilter(string $value): JsonApiException
    {
        return JsonApiException::error([
            'status' => '400',
            'code' => 'invalid_filter',
            'title' => 'Invalid Filter',
            'detail' => "Invalid eventId filter [{$value}]. Expected one or more positive integer event IDs.",
        ]);
    }
}
