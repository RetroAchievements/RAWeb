<?php

declare(strict_types=1);

namespace App\Api\V2\LookingForGroupInvites;

use App\Api\V2\DefaultCollectionQuery;

class LookingForGroupInviteCollectionQuery extends DefaultCollectionQuery
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
                'in:pending,accepted,declined,canceled,expired',
            ],
            'filter.postId' => [
                'nullable',
                'integer',
            ],
            'filter.role' => [
                'nullable',
                'string',
                'in:sent,received',
            ],
        ]);
    }
}
