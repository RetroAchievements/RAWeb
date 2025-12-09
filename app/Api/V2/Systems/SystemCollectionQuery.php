<?php

declare(strict_types=1);

namespace App\Api\V2\Systems;

use LaravelJsonApi\Laravel\Http\Requests\ResourceQuery;
use LaravelJsonApi\Validation\Rule as JsonApiRule;

class SystemCollectionQuery extends ResourceQuery
{
    /**
     * Get the validation rules for the request.
     */
    public function rules(): array
    {
        return [
            'fields' => [
                'nullable',
                'array',
                JsonApiRule::fieldSets(),
            ],

            'filter' => [
                'nullable',
                'array',
                JsonApiRule::filter(),
            ],

            'include' => [
                'nullable',
                'string',
                JsonApiRule::includePaths(),
            ],

            'page' => [
                'nullable',
                'array',
                JsonApiRule::page(),
            ],

            'page.number' => ['integer', 'min:1'],
            'page.size' => ['integer', 'min:1', 'max:100'],

            'sort' => [
                'nullable',
                'string',
                JsonApiRule::sort(),
            ],
        ];
    }
}
