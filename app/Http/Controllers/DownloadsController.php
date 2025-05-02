<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Data\UserPermissionsData;
use App\Http\Controller;
use App\Http\Data\DownloadsPagePropsData;
use App\Models\Emulator;
use App\Models\Platform;
use App\Models\System;
use App\Platform\Data\EmulatorData;
use App\Platform\Data\PlatformData;
use App\Platform\Data\SystemData;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Jenssegers\Agent\Agent;

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
            userDetectedPlatformId: $this->detectUserPlatform($allPlatforms),
            userSelectedSystemId: (int) $request->input('system', null),
            can: UserPermissionsData::fromUser($request->user())->include('manageEmulators'),
        );

        return Inertia::render('downloads', $props);
    }

    /**
     * @param Collection<int, Platform> $allPlatforms
     */
    private function detectUserPlatform(Collection $allPlatforms): ?int
    {
        $agent = new Agent();

        $platformId = null;

        if ($agent->is('Windows') || $agent->is('Windows NT')) {
            $platformId = $allPlatforms->where('name', 'Windows')->first()->id;
        } elseif ($agent->is('OS X') && !$agent->isMobile()) {
            $platformId = $allPlatforms->where('name', 'macOS')->first()->id;
        } elseif ($agent->is('AndroidOS')) {
            $platformId = $allPlatforms->where('name', 'Android')->first()->id;
        } elseif ($agent->is('Debian') || $agent->is('Ubuntu') || $agent->is('OpenBSD') || $agent->is('Linux')) {
            $platformId = $allPlatforms->where('name', 'Linux')->first()->id;
        } elseif ($agent->is('iOS')) {
            $platformId = $allPlatforms->where('name', 'iOS')->first()->id;
        }

        return $platformId;
    }

    /**
     * @param Collection<int, System> $allSystems
     * @return array<int, int[]>
     */
    private function getPopularEmulatorsBySystem(Collection $allSystems): array
    {
        $popularEmulatorsBySystem = [];

        // Check for overall popular emulators (ID 0).
        $overallCacheKey = "popular-emulators-for-system:0";
        if (Cache::has($overallCacheKey)) {
            $popularEmulatorsBySystem[0] = Cache::get($overallCacheKey);
        } else {
            $popularEmulatorsBySystem[0] = [];
        }

        foreach ($allSystems as $system) {
            $cacheKey = "popular-emulators-for-system:{$system->id}";

            $popularEmulatorsBySystem[$system->id] = [];
            if (Cache::has($cacheKey)) {
                $popularEmulatorsBySystem[$system->id] = Cache::get($cacheKey);
            }
        }

        return $popularEmulatorsBySystem;
    }

    /**
     * @return array<int, int>
     */
    private function getTopSystemIds(): array
    {
        $topSystemIds = Cache::get('top-systems');

        // If we don't have any cached top systems at the moment,
        // use a reasonable fallback.
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
