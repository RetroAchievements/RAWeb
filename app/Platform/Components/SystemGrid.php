<?php

declare(strict_types=1);

namespace App\Platform\Components;

use App\Components\Grid;
use App\Models\System;
use Illuminate\Database\Eloquent\Builder;

class SystemGrid extends Grid
{
    public bool $updateQuery = true;

    protected function resourceName(): string
    {
        return 'system';
    }

    protected function columns(): iterable
    {
        return [
            // [
            //     'key' => 'manufacturer',
            //     'label' => false,
            //     // 'label' => 'Manufacturer',
            //     // 'sortBy' => 'manufacturer',
            //     // 'sortDirection' => 'asc',
            // ],
            [
                'key' => 'image',
                'label' => false,
            ],
            [
                'key' => 'name',
                'label' => 'Name',
                // 'sortBy' => 'name',
                // 'sortDirection' => 'asc',
            ],
            [
                'key' => 'achievements',
                'label' => 'Achievements',
                // 'sortBy' => 'achievements',
                // 'sortDirection' => 'desc',
            ],
            [
                'key' => 'games',
                'label' => 'Games',
                // 'sortBy' => 'games',
                // 'sortDirection' => 'desc',
            ],
            [
                'key' => 'emulators',
                'label' => 'Emulators',
                // 'sortBy' => 'emulators',
                // 'sortDirection' => 'desc',
            ],
        ];
    }

    protected function allowedSorts(): array
    {
        return [
        ];
    }

    /**
     * @return Builder<System>
     */
    protected function query(): Builder
    {
        $query = parent::query();

        $query->withCount(['games', 'achievements', 'emulators']);

        return $query;
    }
}
