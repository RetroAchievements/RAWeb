<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Data\UserPermissionsData;
use App\Http\Actions\DetectUserOSAction;
use App\Http\Controller;
use App\Http\Data\DownloadsPagePropsData;
use App\Models\DownloadsPopularityMetric;
use App\Models\Emulator;
use App\Models\Platform;
use App\Models\System;
use App\Platform\Data\EmulatorData;
use App\Platform\Data\PlatformData;
use App\Platform\Data\SystemData;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class DownloadsController extends Controller
{
    public function index(Request $request): InertiaResponse
    {
        $allEmulators = Emulator::query()
            ->with('userAgents', 'downloads', 'platforms', 'systems')
            ->whereNotIn('id', [Emulator::NonEmulator])
            ->get();
        $emulatorsData = $allEmulators->map(function (Emulator $emulator) {
            return EmulatorData::fromEmulator($emulator)->include(
                'documentationUrl',
                'downloads',
                'downloadUrl',
                'downloadX64Url',
                'hasOfficialSupport',
                'originalName',
                'platforms',
                'sourceUrl',
                'systems.iconUrl',
                'systems.nameShort',
                'websiteUrl',
            );
        });

        $allPlatforms = Platform::get();
        $platformsData = $allPlatforms->map(fn (Platform $platform) => PlatformData::from($platform));

        $allSystems = System::where('active', true)->whereNotIn('ID', [System::Events, System::Hubs, System::Standalones])->get();
        $systemsData = $allSystems->map(function (System $system) {
            return SystemData::fromSystem($system)->include('nameShort', 'iconUrl');
        });

        $props = new DownloadsPagePropsData(
            $emulatorsData,
            $platformsData,
            $systemsData,
            topSystemIds: $this->getTopSystemIds(),
            popularEmulatorsBySystem: $this->getPopularEmulatorsBySystem($allSystems),
            userDetectedPlatformId: $this->detectUserPlatformId($allPlatforms),
            userSelectedSystemId: (int) $request->input('system', null),
            can: UserPermissionsData::fromUser($request->user())->include('manageEmulators'),
        );

        return Inertia::render('downloads', $props);
    }

    /**
     * @param Collection<int, Platform> $allPlatforms
     */
    private function detectUserPlatformId(Collection $allPlatforms): ?int
    {
        $userOS = (new DetectUserOSAction())->execute();

        if ($userOS === null) {
            return null;
        }

        $platform = $allPlatforms->where('name', $userOS->value)->first();

        return $platform?->id;
    }

    /**
     * @param Collection<int, System> $allSystems
     * @return array<int, int[]>
     */
    private function getPopularEmulatorsBySystem(Collection $allSystems): array
    {
        $popularEmulatorsBySystem = [];

        // Check for overall popular emulators (ID 0).
        $overallKey = "popular-emulators-for-system:0";
        $overallMetric = DownloadsPopularityMetric::where('key', $overallKey)->first();
        $popularEmulatorsBySystem[0] = $overallMetric ? $overallMetric->ordered_ids : [];

        foreach ($allSystems as $system) {
            $key = "popular-emulators-for-system:{$system->id}";

            $metric = DownloadsPopularityMetric::where('key', $key)->first();
            $popularEmulatorsBySystem[$system->id] = $metric ? $metric->ordered_ids : [];
        }

        return $popularEmulatorsBySystem;
    }

    /**
     * @return array<int, int>
     */
    private function getTopSystemIds(): array
    {
        // Try to load cached popularity metrics from the DB.
        $metric = DownloadsPopularityMetric::where('key', 'top-systems')->first();
        $topSystemIds = $metric ? $metric->ordered_ids : null;

        // If we don't have data in the database, use a reasonable fallback.
        if (!isset($topSystemIds)) {
            $topSystemIds = [
                21, // PlayStation 2
                12, // PlayStation
                5,  // Game Boy Advance
                3,  // SNES/Super Famicom
                7,  // NES/Famicom
                41, // PlayStation Portable
                2,  // Nintendo 64
                1,  // Genesis/Mega Drive
                4,  // Game Boy
                6,  // Game Boy Color
                18, // Nintendo DS
                27, // Arcade
                40, // Dreamcast
                16, // GameCube
                11, // Master System
            ];
        }

        return $topSystemIds;
    }
}
