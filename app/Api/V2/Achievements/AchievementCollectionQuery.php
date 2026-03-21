<?php

declare(strict_types=1);

namespace App\Api\V2\Achievements;

use App\Api\V2\DefaultCollectionQuery;
use App\Api\V2\Rules\CommaDelimitedIn;
use App\Platform\Enums\AchievementType;

class AchievementCollectionQuery extends DefaultCollectionQuery
{
    /**
     * Get the validation rules for the request.
     */
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'filter.type' => [
                'nullable',
                'string',
                new CommaDelimitedIn(AchievementType::cases()),
            ],
        ]);
    }
}
