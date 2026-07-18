<?php

declare(strict_types=1);

namespace App\Api\V2;

use Illuminate\Http\Request;
use LaravelJsonApi\Core\Resources\JsonApiResource;

abstract class BaseJsonApiResource extends JsonApiResource
{
    /**
     * Use the model's primary key instead of route key for API IDs.
     * This avoids issues with models using HasSelfHealingUrls trait
     * which returns slugged route keys that are meant for web URLs.
     */
    public function id(): string
    {
        return (string) $this->resource->getKey();
    }

    protected function wasRelationshipIncluded(?Request $request, string $relationship): bool
    {
        return $this->wasIncluded($request, $relationship);
    }

    /**
     * Did the consumer opt into this relationship via `?include=...`?
     * Matches exact names and dotted paths (eg: `achievement.game`).
     */
    protected function wasIncluded(?Request $request, string $relationship): bool
    {
        if (!$request) {
            return false;
        }

        return collect(explode(',', (string) $request->query('include')))
            ->map(fn (string $include) => trim($include))
            ->contains(fn (string $include) => $include === $relationship || str_starts_with($include, "{$relationship}."));
    }
}
