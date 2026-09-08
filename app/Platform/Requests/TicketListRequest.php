<?php

declare(strict_types=1);

namespace App\Platform\Requests;

use App\Platform\Enums\TicketListScope;
use App\Platform\Enums\TicketListSortField;
use Illuminate\Foundation\Http\FormRequest;

class TicketListRequest extends FormRequest
{
    public function getScope(): TicketListScope
    {
        $scope = $this->route('scope') ?? $this->input('scope');

        return TicketListScope::tryFrom((string) $scope) ?? TicketListScope::All;
    }

    public function rules(): array
    {
        $sortValues = array_merge(
            array_map(fn (TicketListSortField $field) => $field->value, TicketListSortField::cases()),
            array_map(fn (TicketListSortField $field) => '-' . $field->value, TicketListSortField::cases()),
        );

        return [
            'page.number' => 'sometimes|integer|min:1',
            'sort' => 'sometimes|string|in:' . implode(',', $sortValues),
        ];
    }

    public function getPage(): int
    {
        return (int) $this->input('page.number', 1);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getCookiePreferences(): ?array
    {
        return once(function (): ?array {
            $cookie = $this->cookie($this->getScope()->persistenceCookieName());
            if (!is_string($cookie)) {
                return null;
            }

            $preferences = json_decode($cookie, true);

            return is_array($preferences) ? $preferences : null;
        });
    }

    /**
     * @return array{field: TicketListSortField, direction: 'asc'|'desc'}
     */
    public function getSort(): array
    {
        $sortParam = $this->normalizeSortParam($this->input('sort'))
            ?? $this->normalizeSortParam($this->getCookiePreferences()['sortParam'] ?? null)
            ?? '-' . TicketListSortField::CreatedAt->value;

        $isDescending = str_starts_with($sortParam, '-');

        return [
            'field' => TicketListSortField::from(ltrim($sortParam, '-')),
            'direction' => $isDescending ? 'desc' : 'asc',
        ];
    }

    private function normalizeSortParam(mixed $sortParam): ?string
    {
        if (!is_string($sortParam) || $sortParam === '') {
            return null;
        }

        $isDescending = str_starts_with($sortParam, '-');
        $fieldValue = $isDescending ? mb_substr($sortParam, 1) : $sortParam;

        if (TicketListSortField::tryFrom($fieldValue) === null) {
            return null;
        }

        return $isDescending ? "-{$fieldValue}" : $fieldValue;
    }
}
