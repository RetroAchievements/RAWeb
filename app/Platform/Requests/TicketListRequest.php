<?php

declare(strict_types=1);

namespace App\Platform\Requests;

use App\Platform\Enums\TicketListSortField;
use Illuminate\Foundation\Http\FormRequest;

class TicketListRequest extends FormRequest
{
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
     * @return array{field: TicketListSortField, direction: 'asc'|'desc'}
     */
    public function getSort(): array
    {
        $sortParam = $this->input('sort');

        // Newest first is the default sort order.
        if (!is_string($sortParam) || $sortParam === '') {
            return ['field' => TicketListSortField::CreatedAt, 'direction' => 'desc'];
        }

        $direction = 'asc';
        if (str_starts_with($sortParam, '-')) {
            $direction = 'desc';
            $sortParam = ltrim($sortParam, '-');
        }

        return ['field' => TicketListSortField::from($sortParam), 'direction' => $direction];
    }
}
