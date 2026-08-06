<?php

declare(strict_types=1);

namespace App\Support\Casts;

use App\Support\WhitespaceNormalizer;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * @implements CastsAttributes<string|null, string|null>
 */
final class NormalizedText implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        return $value === null ? null : (string) $value;
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value !== null && !is_string($value)) {
            return $value;
        }

        return WhitespaceNormalizer::normalize($value);
    }
}
