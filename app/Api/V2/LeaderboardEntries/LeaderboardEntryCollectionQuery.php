<?php

declare(strict_types=1);

namespace App\Api\V2\LeaderboardEntries;

use App\Api\V2\DefaultCollectionQuery;

class LeaderboardEntryCollectionQuery extends DefaultCollectionQuery
{
    /**
     * Get the validation rules for the request.
     */
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'filter.maxRank' => ['integer', 'min:1', 'max:100'],
        ]);
    }
}
