<?php

declare(strict_types=1);

namespace App\Api\V2\EventAchievements;

use App\Models\EventAchievement;
use Illuminate\Database\Eloquent\Builder;
use LaravelJsonApi\Core\Exceptions\JsonApiException;
use LaravelJsonApi\Eloquent\Contracts\Filter;

final class EventAchievementEvergreenFilter implements Filter
{
    public function key(): string
    {
        return 'evergreen';
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
        $wantsEvergreen = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

        if ($wantsEvergreen === null) {
            throw JsonApiException::error([
                'status' => '400',
                'code' => 'invalid_filter',
                'title' => 'Invalid Filter',
                'detail' => "Invalid evergreen filter [{$value}]. Expected a boolean.",
            ]);
        }

        return $wantsEvergreen
            ? $query->whereNull('active_until')
            : $query->whereNotNull('active_until');
    }
}
