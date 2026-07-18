<?php

declare(strict_types=1);

namespace App\Api\V2\Tickets;

use App\Models\Ticket;
use BackedEnum;
use Illuminate\Database\Eloquent\Builder;
use LaravelJsonApi\Core\Exceptions\JsonApiException;
use LaravelJsonApi\Eloquent\Contracts\Filter;

/**
 * Shared CSV-parse + enum-validate + `all`-keyword bypass for ticket enum filters.
 * Subclasses declare `key`, `column`, `enumClass`, `errorLabel`.
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

        $values = $this->validateEnumValues($raw);

        return $query->whereIn($this->column(), $values);
    }

    abstract protected function column(): string;

    /** @return class-string<BackedEnum> */
    abstract protected function enumClass(): string;

    /** Noun used in 400 responses, eg: "ticket state". */
    abstract protected function errorLabel(): string;

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
    private function validateEnumValues(array $raw): array
    {
        $enumClass = $this->enumClass();

        return collect($raw)
            ->map(function (string $value) use ($enumClass) {
                /** @var BackedEnum|null $case */
                $case = $enumClass::tryFrom($value);

                if (!$case) {
                    throw JsonApiException::error([
                        'status' => '400',
                        'code' => 'invalid_filter',
                        'title' => 'Invalid Filter',
                        'detail' => "Unknown {$this->errorLabel()} [{$value}].",
                    ]);
                }

                return $case->value;
            })
            ->unique()
            ->values()
            ->all();
    }
}
