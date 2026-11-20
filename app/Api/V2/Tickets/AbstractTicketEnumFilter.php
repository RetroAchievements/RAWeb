<?php

declare(strict_types=1);

namespace App\Api\V2\Tickets;

use App\Models\Ticket;
use Illuminate\Database\Eloquent\Builder;
use LaravelJsonApi\Core\Exceptions\JsonApiException;
use LaravelJsonApi\Eloquent\Contracts\Filter;

/**
 * Shared CSV-parse + value-validate + `all`-keyword bypass for ticket enum filters.
 * Subclasses declare `key`, `column`, `resolveValue`, `errorLabel`, and optionally `nullKeyword`.
 */
abstract class AbstractTicketEnumFilter implements Filter
{
    public function isSingular(): bool
    {
        return false;
    }

    /**
     * @param Builder<Ticket> $query
     * @return Builder<Ticket>
     */
    public function apply($query, $value)
    {
        $raw = $this->parseRawValues($value);

        if ($raw === [] || in_array('all', $raw, true)) {
            return $query;
        }

        $nullKeyword = $this->nullKeyword();
        $shouldIncludeNull = $nullKeyword !== null && in_array($nullKeyword, $raw, true);
        $values = $this->validateValues(array_values(array_diff($raw, [$nullKeyword])));

        if (!$shouldIncludeNull) {
            return $query->whereIn($this->column(), $values);
        }

        // group the OR because we don't want it to escape the visibility and state constraints
        return $query->where(function (Builder $valueQuery) use ($values) {
            if ($values !== []) {
                $valueQuery->whereIn($this->column(), $values);
            }

            $valueQuery->orWhereNull($this->column());
        });
    }

    abstract protected function column(): string;

    /**
     * Maps one raw filter token to its column value. Returns null for unknown tokens.
     */
    abstract protected function resolveValue(string $value): int|string|null;

    /** Noun used in 400 responses, eg: "ticket state". */
    abstract protected function errorLabel(): string;

    /**
     * The token that matches rows where the column is null, eg: "none".
     */
    protected function nullKeyword(): ?string
    {
        return null;
    }

    /**
     * @return list<string>
     */
    private function parseRawValues(mixed $value): array
    {
        if (is_array($value)) {
            $value = implode(',', $value);
        }

        return collect(explode(',', (string) $value))
            ->map(fn (string $v) => trim($v))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param list<string> $raw
     * @return list<int|string>
     */
    private function validateValues(array $raw): array
    {
        return collect($raw)
            ->map(function (string $value) {
                $resolved = $this->resolveValue($value);

                if ($resolved === null) {
                    throw JsonApiException::error([
                        'status' => '400',
                        'code' => 'invalid_filter',
                        'title' => 'Invalid Filter',
                        'detail' => "Unknown {$this->errorLabel()} [{$value}].",
                    ]);
                }

                return $resolved;
            })
            ->unique()
            ->values()
            ->all();
    }
}
