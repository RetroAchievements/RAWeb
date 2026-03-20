<?php

declare(strict_types=1);

namespace App\Api\V2\PlayerAchievements;

use App\Api\V2\BaseJsonApiResource;
use App\Models\PlayerAchievement;
use Illuminate\Http\Request;
use LaravelJsonApi\Core\Document\Links;

/**
 * @property PlayerAchievement $resource
 */
class PlayerAchievementResource extends BaseJsonApiResource
{
    /**
     * Get the resource's attributes.
     *
     * @param Request|null $request
     */
    public function attributes($request): iterable
    {
        return [
            'unlockedAt' => $this->resource->unlocked_at,
            'unlockedHardcoreAt' => $this->resource->unlocked_hardcore_at,
        ];
    }

    /**
     * Get the resource's relationships.
     *
     * @param Request|null $request
     */
    public function relationships($request): iterable
    {
        return [
            'achievement' => $this->relation('achievement')->withoutLinks(),
            'game' => $this->relation('game')->withoutLinks(),
            'user' => $this->relation('user')->withoutLinks(),
        ];
    }

    /**
     * @param Request|null $request
     */
    public function links($request): Links
    {
        // Player achievements have no dedicated web URL.
        return new Links();
    }
}
