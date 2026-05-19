<?php

declare(strict_types=1);

namespace App\Api\V2\AchievementSetClaims\Filters;

use App\Models\AchievementSetClaim;
use BackedEnum;
use Illuminate\Database\Eloquent\Builder;
use LaravelJsonApi\Core\Exceptions\JsonApiException;
use LaravelJsonApi\Eloquent\Contracts\Filter;

/**
 * Shared validation/parsing for the four enum-backed claim filters
 * (status, claimType, setType, specialType).
 *
 * Public values are kebab-cased per JSON:API convention (eg: "in-review"),
 * while the underlying enum values use snake_case (eg: "in_review").
 */
abstract class AbstractClaimEnumFilter implements Filter
{
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
        $rawValues = $this->parseRawValues((string) $value);

        if ($rawValues === []) {
            return $query;
        }

        $cases = $this->parseCases($rawValues);

        return $query->whereIn($this->column(), $cases);
    }

    /**
     * The DB column the filter targets.
     */
    abstract protected function column(): string;

    /**
     * The fully-qualified enum class to validate against.
     *
     * @return class-string<BackedEnum>
     */
    abstract protected function enumClass(): string;

    /**
     * Human-readable noun used in error messages, eg: "claim status".
     */
    abstract protected function errorLabel(): string;

    /**
     * @return list<string>
     */
    private function parseRawValues(string $value): array
    {
        return collect(explode(',', $value))
            ->map(fn (string $v) => trim($v))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param  list<string> $rawValues
     * @return list<BackedEnum>
     */
    private function parseCases(array $rawValues): array
    {
        $enumClass = $this->enumClass();

        return collect($rawValues)
            ->map(function (string $rawValue) use ($enumClass) {
                // Public API uses kebab-case. Underlying enums use snake_case.
                $candidate = str_replace('-', '_', $rawValue);

                $case = $enumClass::tryFrom($candidate);

                if (!$case) {
                    throw JsonApiException::error([
                        'status' => '400',
                        'code' => 'invalid_filter',
                        'title' => 'Invalid Filter',
                        'detail' => "Unknown {$this->errorLabel()} [{$rawValue}].",
                    ]);
                }

                return $case;
            })
            ->unique(fn (BackedEnum $case) => $case->value)
            ->values()
            ->all();
    }
}
