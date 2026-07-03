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
        return false;
    }

    /**
     * @param Builder<UserGameListEntry> $query
     * @return Builder<UserGameListEntry>
     */
    public function apply($query, $value)
    {
        $raw = collect(explode(',', (string) $value))
            ->map(fn (string $v) => trim($v))
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($raw === [] || in_array('all', $raw, true)) {
            return $query;
        }

        $kinds = collect($raw)
            ->map(function (string $v) {
                $kind = UserGameListType::tryFrom($v);

                if (!$kind) {
                    throw JsonApiException::error([
                        'status' => '400',
                        'code' => 'invalid_filter',
                        'title' => 'Invalid Filter',
                        'detail' => "Unknown user game list kind [{$v}].",
                    ]);
                }

                return $kind->value;
            })
            ->unique()
            ->values()
            ->all();

        return $query->whereIn('type', $kinds);
    }
}
