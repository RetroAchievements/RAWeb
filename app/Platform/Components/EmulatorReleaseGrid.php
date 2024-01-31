<?php

declare(strict_types=1);

namespace App\Platform\Components;

use App\Components\Grid;

class EmulatorReleaseGrid extends Grid
{
    public bool $updateQuery = true;

    protected function resourceName(): string
    {
        return 'emulator.release';
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
                'label' => __('validation.attributes.archive'),
            ],
            [
                'key' => 'created_at',
                'label' => __('validation.attributes.created_at'),
                'sortBy' => 'created',
                'sortDirection' => 'desc',
            ],
        ];
    }

    // $emulatorReleases = $emulator->releases()->withTrashed()->paginate();
    //     // $minimum = $emulator->releases()->stable()->minimum()->latest()->first();
    // $minimum = null;
    // $stable = $emulator->releases()->stable()->latest()->first();
    // $beta = $emulator->releases()->latest()->first();
    //
    // return view('emulator.release.index')
    // ->with('emulator', $emulator)
    // ->with('grid', $emulatorReleases)
    // ->with('minimum', $minimum)
    // ->with('stable', $stable)
    // ->with('beta', $beta);
}
