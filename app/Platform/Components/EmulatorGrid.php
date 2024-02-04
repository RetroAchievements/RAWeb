<?php

declare(strict_types=1);

namespace App\Platform\Components;

use App\Components\Grid;
use Spatie\QueryBuilder\AllowedSort;

class EmulatorGrid extends Grid
{
    public bool $updateQuery = true;

    protected function resourceName(): string
    {
        return 'emulator';
    }

    protected function columns(): iterable
    {
        return [
            [
                'key' => 'image',
                'label' => false,
            ],
            [
                'key' => 'handle',
                'label' => 'Name',
                // 'sortBy' => 'handle',
                // 'sortDirection' => 'asc',
            ],
            [
                'key' => 'name',
                'label' => __res('emulator', 1),
                // 'sortBy' => 'name',
            ],
            [
                'key' => 'releases',
                'label' => __res('release'),
                // 'sortBy' => 'releases',
                // 'sortDirection' => 'desc',
                'class' => 'text-center',
            ],
            [
                'key' => 'systems',
                'label' => __res('system'),
                // 'sortBy' => 'systems',
                // 'sortDirection' => 'desc',
                'class' => 'text-center',
            ],
            [
                'key' => 'integration_id',
                'label' => __('Integration ID'),
                // 'sortBy' => 'integration_id',
                // 'sortDirection' => 'asc',
            ],
        ];
    }

    protected function defaultSort(): array|string|AllowedSort
    {
        return AllowedSort::field('order', 'order_column');
    }

    protected function allowedSorts(): iterable
    {
        return [
            AllowedSort::field('order', 'order_column'),
            // AllowedSort::custom('order', new SortsNullsAdaptive(), 'order_column'),
        ];
    }

    // $emulators = Emulator::active()
    //     ->with(['systems', 'latestRelease'])
    //     ->withCount(['releases', 'systems'])
    //     ->paginate();
    //
    // $stableIntegration = IntegrationRelease::stable()->latest()->first();
    // $betaIntegration = IntegrationRelease::latest()->first();
    // $integrationReleases = [];
    // if ($stableIntegration) {
    //     $integrationReleases[] = $stableIntegration;
    // }
    // if ($betaIntegration && $betaIntegration->isNot($stableIntegration)) {
    //     $integrationReleases[] = $betaIntegration;
    // }

    // ->with('integrationReleases', collect($integrationReleases))
    // ->with('betaIntegration', $betaIntegration)
    // ->with('grid', $emulators);
}
