<?php

declare(strict_types=1);

namespace App\Support\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait HandlesResources
{
    public static function bootHandlesResources(): void
    {
    }

    protected function resourceName(): string
    {
        return '';
    }

    protected function resourceClass(?string $resourceName = null): mixed
    {
        return resource_class($resourceName ?? $this->resourceName());
    }

    // @phpstan-ignore-next-line
    protected function resourceQuery(?string $resourceName = null): Builder
    {
        return $this->resourceClass($resourceName ?? $this->resourceName())::query();
    }

    public function resourceActionMessage(string $resource, string $action, mixed $value = null, string $result = 'success', int $amount = 1): ?string
    {
        $actionMessage = __("resource.$action.$result", [
            'resource' => __choice("resource.$resource.title", $amount),
            'value' => $value,
        ]);

        return is_string($actionMessage) ? $actionMessage : null;
    }

    public function resourceActionSuccessMessage(string $resource, string $action, mixed $value = null, int $amount = 1): ?string
    {
        return $this->resourceActionMessage($resource, $action, $value, 'success', $amount);
    }

    public function resourceActionErrorMessage(string $resource, string $action, mixed $value = null, int $amount = 1): ?string
    {
        return $this->resourceActionMessage($resource, $action, $value, 'error', $amount);
    }
}
