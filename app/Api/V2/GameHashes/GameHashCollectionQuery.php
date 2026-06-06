<?php

declare(strict_types=1);

namespace App\Api\V2\GameHashes;

use App\Api\V2\DefaultCollectionQuery;
use App\Api\V2\Rules\CommaDelimitedIn;
use App\Enums\GameHashCompatibility;

class GameHashCollectionQuery extends DefaultCollectionQuery
{
    /**
     * Get the validation rules for the request.
     */
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'filter.compatibility' => [
                'nullable',
                'string',
                new CommaDelimitedIn(array_map(fn ($case) => $case->value, GameHashCompatibility::cases())),
            ],
        ]);
    }
}
