<?php

declare(strict_types=1);

namespace App\Api\V2\MessageThreads;

use App\Api\V2\DefaultCollectionQuery;

class MessageThreadCollectionQuery extends DefaultCollectionQuery
{
    /**
     * Get the validation rules for the request.
     */
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'filter.isUnread' => [
                'nullable',
                'boolean',
            ],
        ]);
    }
}
