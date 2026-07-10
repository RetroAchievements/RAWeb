<?php

declare(strict_types=1);

namespace App\Api\V2\UserGameListEntries;

use App\Community\Enums\UserGameListType;
use App\Models\UserGameListEntry;
use Illuminate\Database\Eloquent\Builder;
use LaravelJsonApi\Core\Exceptions\JsonApiException;
use LaravelJsonApi\Eloquent\Contracts\Filter;

final class UserGameListEntryKindFilter implements Filter
{
    public function key(): string
    {
        return 'kind';
    }

    public function isSingular(): bool
    {
        return true;
    }

    /**
     * @param Builder<UserGameListEntry> $query
     * @return Builder<UserGameListEntry>
     */
    public function apply($query, $value)
    {
        return $query->where('type', self::parse($value)->value);
    }

    public static function parse(mixed $value): UserGameListType
    {
        $normalizedValue = trim((string) $value);
        $kind = UserGameListType::tryFrom($normalizedValue);

        if (!$kind) {
            throw JsonApiException::error([
                'status' => '400',
                'code' => 'invalid_filter',
                'title' => 'Invalid Filter',
                'detail' => "Unknown user game list kind [{$normalizedValue}].",
            ]);
        }

        return $kind;
    }
}
