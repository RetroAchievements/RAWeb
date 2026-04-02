<?php

declare(strict_types=1);

namespace App\Api\V2\GameInvites;

use App\Api\V2\DefaultCollectionQuery;

class GameInviteCollectionQuery extends DefaultCollectionQuery
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
            'filter.gameId' => [
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
