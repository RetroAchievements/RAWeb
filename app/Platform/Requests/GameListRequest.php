<?php

declare(strict_types=1);

namespace App\Platform\Requests;

use App\Platform\Enums\GameListProgressFilterValue;
use App\Platform\Enums\GameListSortField;
use Exception;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class GameListRequest extends FormRequest
{
    // (!!) Be sure to do performance testing on any default higher than 100.
    // Note that mobile _ALWAYS_ uses a page size of 100.
    private const DEFAULT_PAGE_SIZE = 25;
    private const VALID_PAGE_SIZES = [10, 25, 50, 100, 200];

    private string $persistenceCookieName = 'datatable_view_preference_generic_games';
    private ?array $cookiePreferences = null;

    public function setPersistenceCookieName(string $name): self
    {
        $this->persistenceCookieName = $name;

        return $this;
    }

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

    public function getCookiePreferences(): ?array
    {
        if (!isset($this->cookiePreferences)) {
            $cookie = $this->cookie($this->persistenceCookieName);

            if ($cookie) {
                try {
                    // The cookie value is written using `btoa()`, which
                    // base64 encodes the stringified JSON value to save space.
                    // We'll decode base64 first, then decode JSON.

                    $decodedValue = base64_decode($cookie, true);
                    if ($decodedValue === false) {
                        return null;
                    }

                    $this->cookiePreferences = json_decode($decodedValue, true);
                } catch (Exception $e) {
                    $this->cookiePreferences = null;
                }
            }
        }

        return $this->cookiePreferences;
    }

    public function getPage(): int
    {
        return (int) $this->input('page.number', 1);
    }

    public function getPageSize(): int
    {
        // URL params take precedence over cookie preferences.
        if ($this->has('page.size')) {
            return (int) $this->input('page.size', self::DEFAULT_PAGE_SIZE);
        }

        // If no URL param, check the cookie next.
        $preferences = $this->getCookiePreferences();
        if ($preferences && isset($preferences['pagination']['pageSize'])) {
            $cookieSize = (int) $preferences['pagination']['pageSize'];

            return in_array($cookieSize, self::VALID_PAGE_SIZES) ? $cookieSize : self::DEFAULT_PAGE_SIZE;
        }

        return self::DEFAULT_PAGE_SIZE;
    }

    /**
     * @return array{field: string, direction: 'asc'|'desc'}
     */
    public function getSort(): array
    {
        // URL params take precedence over cookie preferences.
        $sortParam = $this->input('sort');

        // If no URL param, check the cookie next.
        if ($sortParam === null) {
            $preferences = $this->getCookiePreferences();
            if ($preferences && !empty($preferences['sorting'])) {
                $sorting = $preferences['sorting'][0] ?? null;
                if ($sorting) {
                    $sortParam = $sorting['desc'] ? "-{$sorting['id']}" : $sorting['id'];
                }
            }
        }

        // If we still don't have a sort param, fall back to sorting by title.
        $sortParam ??= GameListSortField::Title->value;

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

        // URL params take precedence over cookie preferences.
        foreach ($this->query('filter', []) as $key => $value) {
            $filters[$key] = explode(',', $value);
        }

        // If no URL params, check the cookie next.
        if (empty($filters)) {
            $preferences = $this->getCookiePreferences();
            if ($preferences && !empty($preferences['columnFilters'])) {
                foreach ($preferences['columnFilters'] as $filter) {
                    $value = $filter['value'];
                    $filters[$filter['id']] = is_array($value) ? $value : [$value];
                }
            }
        }

        // Apply defaults after checking both the URL and the persistence cookie.
        if (!isset($filters['achievementsPublished'])) {
            $filters['achievementsPublished'] = [$defaultAchievementsPublishedFilter];
        }

        if (!is_null($targetSystemId)) {
            $filters['system'] = [$targetSystemId];
        }

        return $filters;
    }
}
