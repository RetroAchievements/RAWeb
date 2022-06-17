<?php

declare(strict_types=1);

namespace App\Support\Database\Eloquent\Concerns;

use Exception;

trait PreventLazyLoading
{
    public function getRelationshipFromMethod($method)
    {
        if (!config('database.prevent_lazy_loading')) {
            return parent::getRelationshipFromMethod($method);
        }

        $whitelist = $this->allowedLazyRelations ?? [];

        if ($this->exists && !in_array($method, $whitelist)) {
            $model = $this::class;

            throw new Exception("Tried to lazily load '{$method}' in {$model}");
        }

        return parent::getRelationshipFromMethod($method);
    }
}
