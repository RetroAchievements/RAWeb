<?php

declare(strict_types=1);

namespace App\Community\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserGameListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'page.number' => 'integer|min:1',
            'sort' => 'string|in:title,system,achievementsPublished,pointsTotal,retroRatio,lastUpdated,releasedAt,playersTotal,numVisibleLeaderboards,numUnresolvedTickets,progress,-title,-system,-achievementsPublished,-pointsTotal,-retroRatio,-lastUpdated,-releasedAt,-playersTotal,-numVisibleLeaderboards,-numUnresolvedTickets,-progress',
            'filter.*' => 'string',
        ];
    }

    public function getPage(): int
    {
        return (int) $this->input('page.number', 1);
    }

    public function getSort(): array
    {
        $sortParam = $this->input('sort', 'title');
        $sortDirection = 'asc';

        if (str_starts_with($sortParam, '-')) {
            $sortDirection = 'desc';
            $sortParam = ltrim($sortParam, '-');
        }

        return [
            'field' => $sortParam,
            'direction' => $sortDirection,
        ];
    }

    public function getFilters(): array
    {
        $filters = [];
        foreach ($this->query('filter', []) as $key => $value) {
            $filters[$key] = explode(',', $value);
        }

        return $filters;
    }
}
