<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Emulator;
use App\Models\EmulatorRelease;
use App\Models\IntegrationRelease;
use Illuminate\Database\Seeder;

class ReleaseTablesSeeder extends Seeder
{
    public function run(): void
    {
        $releasesData = getReleasesFromFile() ?? [];

        if (!empty($releasesData['integration']) !== null && IntegrationRelease::count() === 0) {
            $this->seedIntegrationReleases($releasesData['integration']);
        }

        if (!empty($releasesData['emulators']) !== null && EmulatorRelease::count() === 0) {
            $this->seedEmulatorReleases($releasesData['emulators']);
        }
    }

    private function seedIntegrationReleases(array $data): void
    {
        $releases = $this->extractReleases($data);

        foreach ($releases as $release) {
            IntegrationRelease::create($release);
        }
    }

    private function seedEmulatorReleases(array $releasesData): void
    {
        foreach ($releasesData as $integrationId => $data) {
            $releases = $this->extractReleases($data);

            if (!empty($releases)) {
                Emulator::firstWhere('name', $data['handle'])
                    ->releases()
                    ->createMany($releases);
            }
        }
    }

    private function extractReleases(array $data): array
    {
        $releases = [];

        if (!empty($data['minimum_version'])) {
            $releases[] = [
                'version' => $data['minimum_version'],
                'minimum' => true,
                'stable' => true,
            ];
        }

        if (!empty($data['latest_version']) && $data['latest_version'] !== ($data['minimum_version'] ?? false)) {
            $releases[] = [
                'version' => $data['latest_version'],
                'minimum' => false,
                'stable' => true,
            ];
        }

        return $releases;
    }
}
