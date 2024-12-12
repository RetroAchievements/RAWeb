<?php

declare(strict_types=1);

namespace App\Platform\Requests;

use App\Platform\Enums\GameListProgressFilterValue;
use App\Platform\Enums\GameListSortField;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class GameListRequest extends FormRequest
{
    // (!!) Be sure to do performance testing on any default higher than 100.
    // Note that mobile _ALWAYS_ uses a page size of 100.
    private const DEFAULT_PAGE_SIZE = 25;
    private const VALID_PAGE_SIZES = [10, 25, 50, 100, 200];

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
            'page.size' => 'integer|in:' . implode(',', self::VALID_PAGE_SIZES),
            'sort' => 'string|in:' . implode(',', $sortValues),
            'filter.*' => 'string',
            'filter.progress' => [
                'nullable',
                'string',
                new Enum(GameListProgressFilterValue::class),
            ],
        ];
    }

    public function getPage(): int
    {
        return (int) $this->input('page.number', 1);
    }

    public function getPageSize(): int
    {
        return (int) $this->input('page.size', self::DEFAULT_PAGE_SIZE);
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
     * @param int|null $targetSystemId used when changing the system is not available, ie: system game lists
     * @return array<string, array<string>>
     */
    public function getFilters(string $defaultAchievementsPublishedFilter = 'has', ?int $targetSystemId = null): array
    {
        $filters = [];
        foreach ($this->query('filter', []) as $key => $value) {
            $filters[$key] = explode(',', $value);
        }

        if (!isset($filters['achievementsPublished'])) {
            $filters['achievementsPublished'] = [$defaultAchievementsPublishedFilter];
        }

        if (!is_null($targetSystemId)) {
            $filters['system'] = [$targetSystemId];
        }

        return $filters;
    }
}
