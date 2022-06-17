<?php

declare(strict_types=1);

namespace App\Platform\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait HasStabilityFlags
{
    public static function bootHasStabilityFlags(): void
    {
    }

    // == accessors

    // == relations

    // == scopes

    public function scopeStable(Builder $query): Builder
    {
        return $query->where('stable', true);
    }

    public function scopeUnstable(Builder $query): Builder
    {
        return $query->where('stable', false);
    }

    public function scopeMinimum(Builder $query): Builder
    {
        return $query->where('minimum', true);
    }
}
