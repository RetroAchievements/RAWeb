<?php

declare(strict_types=1);

namespace App\Platform\Components;

use App\Components\Grid;

class IntegrationReleaseGrid extends Grid
{
    public bool $updateQuery = true;

    protected function resourceName(): string
    {
        return 'integration.release';
    }

    protected function columns(): iterable
    {
        return [
            [
                'key' => 'version',
                'label' => __('validation.attributes.version'),
                // 'sortBy' => 'version',
                // 'sortDirection' => 'asc',
            ],
            [
                'key' => 'stability',
                'label' => __('validation.attributes.stability'),
                'class' => 'text-center',
            ],
            [
                'key' => 'build_x86',
                'label' => __('validation.attributes.build'),
            ],
            [
                'key' => 'created_at',
                'label' => __('validation.attributes.created_at'),
                'sortBy' => 'created',
                'sortDirection' => 'desc',
            ],
        ];
    }

    // $integrationReleases = IntegrationRelease::withTrashed()->paginate();
    // $minimum = IntegrationRelease::stable()->minimum()->latest()->first();
    // $stable = IntegrationRelease::stable()->latest()->first();
    // $beta = IntegrationRelease::latest()->first();
    //
    // return view('integration.release.index')
    //     ->with('grid', $integrationReleases)
    //     ->with('minimum', $minimum)
    //     ->with('stable', $stable)
    //     ->with('beta', $beta);
}
