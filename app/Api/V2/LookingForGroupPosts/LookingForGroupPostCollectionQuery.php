<?php

declare(strict_types=1);

namespace App\Api\V2\LookingForGroupPosts;

use App\Api\V2\DefaultCollectionQuery;

class LookingForGroupPostCollectionQuery extends DefaultCollectionQuery
{
    /**
     * Get the validation rules for the request.
     */
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'filter.status' => [
                'nullable',
                'string',
                'in:active,filled,cancelled,expired',
            ],
            'filter.gameId' => [
                'nullable',
                'integer',
            ],
            'filter.hasSpace' => [
                'nullable',
                'boolean',
            ],
            'filter.scheduledAfter' => [
                'nullable',
                'date',
            ],
            'filter.scheduledBefore' => [
                'nullable',
                'date',
            ],
        ]);
    }
}
