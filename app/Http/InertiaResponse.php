<?php

namespace App\Http;

use Illuminate\Http\Request;
use Inertia\Response;

class InertiaResponse extends Response
{
    /**
     * Resolve all arrayables properties into an array, removing null values.
     */
    public function resolveArrayableProperties(array $props, Request $request, bool $unpackDotProps = true): array
    {
        $props = parent::resolveArrayableProperties($props, $request, $unpackDotProps);

        return $this->removeNulls($props);
    }

    /**
     * Recursively remove null values from an array.
     */
    protected function removeNulls(array $data): array
    {
        return collect($data)->map(function ($value) {
            if (is_array($value)) {
                return $this->removeNulls($value);
            }

            return $value;
        })->filter(function ($value) {
            return $value !== null;
        })->toArray();
    }
}
