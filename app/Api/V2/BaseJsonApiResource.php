<?php

declare(strict_types=1);

namespace App\Api\V2;

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
}
