<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controller;
use App\Models\Emulator;
use App\Models\IntegrationRelease;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;

class DownloadController extends Controller
{
    public function index(): View
    {
        /** @var Collection<int, Emulator> $emulators */
        $emulators = Emulator::ordered()->active()
            ->with([
                'systems' => function ($query) {
                    $query->orderBy('manufacturer');
                    $query->orderBy('name');
                },
                'latestRelease',
                'latestBetaRelease',
            ])
            ->withCount(['releases', 'systems'])
            ->get();

        $stableIntegration = IntegrationRelease::stable()->latest()->first();
        $betaIntegration = IntegrationRelease::latest()->first();
        $integrationReleases = [];
        if ($stableIntegration) {
            $integrationReleases[] = $stableIntegration;
        }

        if (
            $betaIntegration
            && $betaIntegration->isNot($stableIntegration)
            && version_compare($betaIntegration->version, $stableIntegration->version, '>=')
        ) {
            $integrationReleases[] = $betaIntegration;
        }

        $integrationReleases = collect($integrationReleases);

        return view('download')
            ->with('integrationReleases', $integrationReleases)
            ->with('emulators', $emulators);
    }
}
