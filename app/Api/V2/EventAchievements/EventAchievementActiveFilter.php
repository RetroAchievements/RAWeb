<?php

declare(strict_types=1);

namespace App\Api\V2\EventAchievements;

use App\Models\EventAchievement;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use LaravelJsonApi\Core\Exceptions\JsonApiException;
use LaravelJsonApi\Eloquent\Contracts\Filter;

final class EventAchievementActiveFilter implements Filter
{
    public function key(): string
    {
        return 'active';
    }

    public function isSingular(): bool
    {
        return true;
    }

    /**
     * @param Builder<EventAchievement> $query
     * @return Builder<EventAchievement>
     */
    public function apply($query, $value)
    {
        $wantsActive = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

        if ($wantsActive === null) {
            throw JsonApiException::error([
                'status' => '400',
                'code' => 'invalid_filter',
                'title' => 'Invalid Filter',
                'detail' => "Invalid active filter [{$value}]. Expected a boolean.",
            ]);
        }

        if ($wantsActive) {
            return $query->active();
        }

        $now = Carbon::now();

        return $query->where(function ($q) use ($now) {
            $q->where('active_from', '>', $now)
                ->orWhere('active_until', '<=', $now);
        });
    }
}
