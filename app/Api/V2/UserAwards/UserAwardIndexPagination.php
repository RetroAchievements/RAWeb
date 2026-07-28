<?php

declare(strict_types=1);

namespace App\Api\V2\UserAwards;

use LaravelJsonApi\Eloquent\Pagination\PagePagination;

/**
 * Pins the pagination key to awarded_at so we don't accidentally defeat
 * the awarded_at index when doing an "order by ID" tiebreaker.
 */
class UserAwardIndexPagination extends PagePagination
{
    public function withKeyName(string $column): self
    {
        return parent::withKeyName('awarded_at');
    }
}
