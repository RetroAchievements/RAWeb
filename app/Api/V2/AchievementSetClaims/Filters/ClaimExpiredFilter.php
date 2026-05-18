<?php

declare(strict_types=1);

namespace App\Api\V2\AchievementSetClaims\Filters;

use App\Community\Enums\ClaimStatus;
use App\Models\AchievementSetClaim;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use LaravelJsonApi\Core\Exceptions\JsonApiException;
use LaravelJsonApi\Eloquent\Contracts\Filter;

/**
 * Filters active or in-review claims by whether their `finished_at` is past.
 *
 * In the claims domain, "expired" only applies to claims that are still
 * outstanding. A completed claim's `finished_at` is its completion timestamp.
 * A dropped claim's `finished_at` is its drop timestamp. Neither of those
 * states is "expired", so this filter scopes to active/in-review first.
 */
final class ClaimExpiredFilter implements Filter
{
    public function key(): string
    {
        return 'expired';
    }

    public function isSingular(): bool
    {
        return false;
    }

    /**
     * @param Builder<AchievementSetClaim> $query
     * @return Builder<AchievementSetClaim>
     */
    public function apply($query, $value)
    {
        $expired = $this->parseBool((string) $value);

        $query->whereIn('status', [ClaimStatus::Active, ClaimStatus::InReview]);

        return $expired
            ? $query->where('finished_at', '<', Carbon::now())
            : $query->where('finished_at', '>=', Carbon::now());
    }

    private function parseBool(string $value): bool
    {
        return match (strtolower(trim($value))) {
            'true', '1' => true,
            'false', '0' => false,
            default => throw JsonApiException::error([
                'status' => '400',
                'code' => 'invalid_filter',
                'title' => 'Invalid Filter',
                'detail' => "Unknown expired filter value [{$value}]; expected true or false.",
            ]),
        };
    }
}
