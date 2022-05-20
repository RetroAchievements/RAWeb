<?php

declare(strict_types=1);

namespace App\Platform\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

trait HasStabilityFlags
{
    public static function bootHasStabilityFlags(): void
    {
    }

    // == accessors

    // == relations

    // == scopes

    /**
     * @param Builder<Model> $query
     * @return Builder<Model>
     */
    public function scopeStable(Builder $query): Builder
    {
        return $query->where('stable', true);
    }

    /**
     * @param Builder<Model> $query
     * @return Builder<Model>
     */
    public function scopeUnstable(Builder $query): Builder
    {
        return $query->where('stable', false);
    }

    /**
     * @param Builder<Model> $query
     * @return Builder<Model>
     */
    public function scopeMinimum(Builder $query): Builder
    {
        return $query->where('minimum', true);
    }
}
