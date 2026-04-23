<?php

declare(strict_types=1);

namespace App\Api\V2\UserAwards;

use App\Models\PlayerBadge;
use Illuminate\Database\Eloquent\Builder;
use LaravelJsonApi\Core\Exceptions\JsonApiException;
use LaravelJsonApi\Eloquent\Contracts\Filter;

final class UserAwardKindFilter implements Filter
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
     * @param Builder<PlayerBadge> $query
     * @return Builder<PlayerBadge>
     */
    public function apply($query, $value)
    {
        $rawKinds = $this->parseRawKinds((string) $value);

        if ($rawKinds === []) {
            return $query;
        }

        $kinds = $this->parseKinds($rawKinds);

        return $query->where(function (Builder $query) use ($kinds) {
            foreach ($kinds as $kind) {
                $query->orWhere(fn (Builder $query) => $kind->apply($query));
            }
        });
    }

    /**
     * @return list<string>
     */
    private function parseRawKinds(string $value): array
    {
        return collect(explode(',', $value))
            ->map(fn (string $kind) => trim($kind))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return list<UserAwardKind>
     */
    private function parseKinds(array $kinds): array
    {
        return collect($kinds)
            ->map(function (string $kind) {
                $parsedKind = UserAwardKind::tryFrom(trim($kind));

                if (!$parsedKind) {
                    throw JsonApiException::error([
                        'status' => '400',
                        'code' => 'invalid_filter',
                        'title' => 'Invalid Filter',
                        'detail' => "Unknown user award kind [{$kind}].",
                    ]);
                }

                return $parsedKind;
            })
            ->unique(fn (UserAwardKind $kind) => $kind->value)
            ->values()
            ->all();
    }
}
