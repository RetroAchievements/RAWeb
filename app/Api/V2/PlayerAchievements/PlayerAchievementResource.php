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
        // Only include the "other" relationship identifier to avoid redundancy.
        // When accessed via a parent, that parent's identity is already known from the URL.
        $parentResource = self::resolveParentResource($request);

        $relationships = [
            'game' => $this->relation('game')->withoutLinks(),
        ];

        if ($parentResource !== 'achievements') {
            $relationships['achievement'] = $this->relation('achievement')->withoutLinks()->showDataIfLoaded();
        }

        if ($parentResource !== 'users') {
            $relationships['user'] = $this->relation('user')->withoutLinks()->showDataIfLoaded();
        }

        return $relationships;
    }

    /**
     * @param Request|null $request
     */
    public function links($request): Links
    {
        // Player achievements have no dedicated web URL.
        return new Links();
    }

    /**
     * Determines the parent resource type from the route name.
     * Cached per-request so the lookup isn't repeated for every resource in the collection.
     */
    private static function resolveParentResource(?Request $request): ?string
    {
        $routeName = $request?->route()?->getName() ?? '';

        // The pattern is "v2.{parentResource}.playerAchievements".
        if (preg_match('/^v2\.(\w+)\.playerAchievements/', $routeName, $matches)) {
            return $matches[1];
        }

        return null;
    }
}
