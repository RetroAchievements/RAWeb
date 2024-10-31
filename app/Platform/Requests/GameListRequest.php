<?php

declare(strict_types=1);

namespace App\Platform\Requests;

use App\Platform\Enums\GameListSortField;
use Illuminate\Foundation\Http\FormRequest;

class GameListRequest extends FormRequest
{
    public function rules(): array
    {
        // Get all valid sort values with and without the "-" prefix.
        $sortValues = array_merge(
            // Ascending sorts.
            array_map(fn (GameListSortField $field) => $field->value, GameListSortField::cases()),
            // Descending sorts with the "-" prefix.
            array_map(fn (GameListSortField $field) => '-' . $field->value, GameListSortField::cases())
        );

        return [
            'page.number' => 'integer|min:1',
            'sort' => 'string|in:' . implode(',', $sortValues),
            'filter.*' => 'string',
        ];
    }

    public function getPage(): int
    {
        return (int) $this->input('page.number', 1);
    }

    /**
     * @return array{field: string, direction: 'asc'|'desc'}
     */
    public function getSort(): array
    {
        $sortParam = $this->input('sort', GameListSortField::Title->value);
        $sortDirection = 'asc';

        if (str_starts_with($sortParam, '-')) {
            $sortDirection = 'desc';
            $sortParam = ltrim($sortParam, '-');
        }

        /** @var GameListSortField $sortField */
        $sortField = GameListSortField::from($sortParam);

        return [
            'field' => $sortField->value,
            'direction' => $sortDirection,
        ];
    }

    /**
     * @return array<string, array<string>>
     */
    public function getFilters(): array
    {
        $filters = [];
        foreach ($this->query('filter', []) as $key => $value) {
            $filters[$key] = explode(',', $value);
        }

        if (!isset($filters['achievementsPublished'])) {
            $filters['achievementsPublished'] = ['has'];
        }

        return $filters;
    }
}
