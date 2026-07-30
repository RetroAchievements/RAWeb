<?php

declare(strict_types=1);

namespace App\Api\V2\PlayerAchievementSets;

use App\Models\PlayerAchievementSet;
use Illuminate\Database\Eloquent\Builder;
use LaravelJsonApi\Core\Exceptions\JsonApiException;
use LaravelJsonApi\Eloquent\Contracts\Filter;

final class PlayerAchievementSetAwardKindFilter implements Filter
{
    public function key(): string
    {
        return 'awardKind';
    }

    public function isSingular(): bool
    {
        return false;
    }

    /**
     * @param Builder<PlayerAchievementSet> $query
     * @return Builder<PlayerAchievementSet>
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
     * @param list<string> $kinds
     * @return list<PlayerAchievementSetAwardKind>
     */
    private function parseKinds(array $kinds): array
    {
        return collect($kinds)
            ->map(function (string $kind) {
                $parsedKind = PlayerAchievementSetAwardKind::tryFrom(trim($kind));

                if (!$parsedKind) {
                    throw JsonApiException::error([
                        'status' => '400',
                        'code' => 'invalid_filter',
                        'title' => 'Invalid Filter',
                        'detail' => "Unknown award kind [{$kind}].",
                    ]);
                }

                return $parsedKind;
            })
            ->unique(fn (PlayerAchievementSetAwardKind $kind) => $kind->value)
            ->values()
            ->all();
    }
}
