<?php

declare(strict_types=1);

namespace App\Api\V2\Users;

use App\Api\V2\DefaultCollectionQuery;
use Illuminate\Validation\Rule;

class UserCollectionQuery extends DefaultCollectionQuery
{
    /**
     * Get the validation rules for the request.
     */
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'filter.ranked' => [
                'nullable',
                'string',
                Rule::in(['true', 'false', '1', '0']),
            ],
        ]);
    }
}
